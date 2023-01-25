<?php
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    setlocale(LC_ALL, 'nld_nld');
} else {
    setlocale(LC_ALL, 'nl_NL');
}

class CoachCalculator
{
    public function __construct($teams, $coachteamId)
    {
        $this->teams = $teams;
        $this->coachteamId = $coachteamId;
    }

    public function GetWedstrijdoverzicht()
    {
        $allWedstrijden = [];

        foreach ($this->teams as $team) {
            foreach ($team->wedstrijden as $wedstrijd) {
                $newWedstrijd = $wedstrijd;
                $newWedstrijd->isCoachteam = $team->isCoachteam;
                $allWedstrijden[] = $newWedstrijd;
            }
        }

        usort($allWedstrijden, ['CoachCalculator', 'wedstrijdvergelijker']);

        $result = (object) [
            "dagen" => [],
            "samenvatting" => [
                "yes" => 0,
                "no" => 0,
                "maybe" => 0
            ]
        ];

        if (count($allWedstrijden) == 0) {
            return $result;
        }

        $currentDay = $this->initializeDay($allWedstrijden[0]);
        foreach ($allWedstrijden as $wedstrijd) {
            $date = $this->GetTimestampString($wedstrijd);
            if ($date != $currentDay->datum) {
                $this->finalizeDay($result, $currentDay);
                $currentDay = $this->initializeDay($wedstrijd);
            }

            $currentDay->wedstrijden[] = $wedstrijd;
        }
        $this->finalizeDay($result, $currentDay);

        return $result;
    }

    private function finalizeDay(&$result, &$day)
    {
        $bestOption = $this->getBestOption($day);

        $coachwedstrijd = $this->getCoachWedstrijd($day->wedstrijden);
        if ($coachwedstrijd != null) {
            $result->samenvatting[$bestOption]++;
            $day->isCoachingPossible = $bestOption;
        }

        $result->dagen[] = $day;
    }

    private function getBestOption($day)
    {
        $wedstrijden = $day->wedstrijden;
        $numberOfWedstrijden = count($day->wedstrijden);

        $bestOption = "no";

        if ($numberOfWedstrijden < count($this->teams)) {
            $bestOption = "yes";
        } else if ($numberOfWedstrijden == 1 && $wedstrijden[0]->isCoachteam) {
            return "yes";
        } else {
            $coachwedstrijd = $this->getCoachWedstrijd($day->wedstrijden);
            foreach ($wedstrijden as $wedstrijd) {
                $coachingOption = $this->GetCoachingOption($coachwedstrijd, $wedstrijd);
                if ($coachingOption == "yes") {
                    $bestOption = "yes";
                } else if ($coachingOption == "maybe" && $bestOption != "yes") {
                    $bestOption = "maybe";
                }
            }
        }

        return $bestOption;
    }

    private function getCoachWedstrijd($wedstrijden)
    {
        foreach ($wedstrijden as $wedstrijd) {
            if ($wedstrijd->isCoachteam) {
                return $wedstrijd;
            }
        }
        return null;
    }

    private function initializeDay($wedstrijd)
    {
        return (object) [
            "datum" => $this->GetTimestampString($wedstrijd),
            "wedstrijden" => []
        ];
    }

    private function GetTimestampString($wedstrijd)
    {
        $timestamp = $wedstrijd->timestamp;
        if ($wedstrijd == null || $timestamp == null) {
            return "--:--";
        }

        return strftime("%a %e %B %G", $timestamp->getTimestamp());
    }

    private function wedstrijdvergelijker($wedstrijd1, $wedstrijd2)
    {
        $timestamp1 = $wedstrijd1->timestamp;
        $timestamp2 = $wedstrijd2->timestamp;
        if ($timestamp1 == null && $timestamp2 == null) {
            return 0;
        }
        if ($timestamp1 == null) {
            return -1;
        }

        if ($timestamp2 == null) {
            return 1;
        }
        if ($timestamp1 == $timestamp2) {
            if ($wedstrijd1->isCoachteam) {
                return -1;
            } else if ($wedstrijd2->isCoachteam) {
                return 1;
            } else {
                return $wedstrijd1->team1 . $wedstrijd1->team2 > $wedstrijd2->team1 . $wedstrijd2->team2;
            }
        }

        return $timestamp1 > $timestamp2;
    }

    private function GetCoachingOption($coachwedstrijd, $wedstrijd)
    {
        if (
            $coachwedstrijd == null || $coachwedstrijd->timestamp == null ||
            $wedstrijd == null || $wedstrijd->timestamp == null
        ) {
            return "yes";
        }

        if ($coachwedstrijd->id == $wedstrijd->id) {
            return "no";
        }
        $timeDifference = $this->getTimeDifference($coachwedstrijd->timestamp, $wedstrijd->timestamp);
        if ($coachwedstrijd->locatie == $wedstrijd->locatie && $timeDifference >= 1.75) {
            return "yes";
        }
        if ($coachwedstrijd->locatie != $wedstrijd->locatie) {
            if ($timeDifference >= 4) {
                return "yes";
            }
            if ($timeDifference >= 3) {
                return "maybe";
            }
            if ($timeDifference >= 2) {
                return "no";
            }
        }

        return "no";
    }

    private function getTimeDifference($timestamp1, $timestamp2)
    {
        $difference = $timestamp1->diff($timestamp2);
        return ($difference->y * 365.25 + $difference->m * 30 + $difference->d) * 24 + $difference->h + $difference->i / 60;
    }
}
