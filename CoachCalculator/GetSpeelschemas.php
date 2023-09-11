<?php
include 'CoachCalculator.php';
include 'NevoboRssReader.php';

$nevoboGateway = new NevoboGateway();

function IsTeamInvalid($team)
{
    $isInputTeam = "/^\S+-\S{2}-\d+$/i";
    $isMatch = preg_match($isInputTeam, $team);
    return $isMatch == false || $isMatch == 0;
}

function GetTeamInfo($teamId)
{
    global $nevoboGateway;
    if (!$teamId) {
        return null;
    }
    if (IsTeamInvalid($teamId)) {
        exit("Teamnaam is incorrect: $teamId");
    }

    $verenigingscode = substr($teamId, 0, 7);
    $geslacht = substr($teamId, 12, 2);
    $volgnummer = substr($teamId, 15);

    // $verenigingscode = "ckl9r53";
    // $geslacht = "ds";
    // $volgnummer = "1";

    return (object) [
        "id" => $teamId,
        "isCoachteam" => false,
        "wedstrijden" => $nevoboGateway->GetProgrammaForExternTeam($verenigingscode, $geslacht, $volgnummer),
    ];
}

$queryString = $_SERVER['QUERY_STRING'];
parse_str($queryString, $queryVariables);
$queryVariables = ((object) $queryVariables);
$coachteamId = $queryVariables->coachteamId ?? null;
$eigenTeamIds = $queryVariables->eigenTeamIds ?? null;
$teams = [];

if ($coachteamId) {
    $coachTeam = GetTeamInfo($coachteamId);
    $coachTeam->isCoachteam = true;
    $teams[] = $coachTeam;
}

if ($eigenTeamIds) {
    foreach ($eigenTeamIds as $eigenTeamId) {
        if ($eigenTeamId != $coachteamId) {
            $teams[] = GetTeamInfo($eigenTeamId);
        }
    }
}


$calculator = new CoachCalculator($teams, $coachteamId);
$wedstrijdoverzicht = $calculator->GetWedstrijdoverzicht();

exit(json_encode($wedstrijdoverzicht));
