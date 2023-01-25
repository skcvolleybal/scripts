var coachTeamId = "";
var eigenTeamIds = [];

$("#coachteam").dropdown({
  apiSettings: {
    url: "nevobo-search.php?q={query}"
  },
  maxSelections: 1,
  onChange: function(value, text, $selectedItem) {
    coachTeamId = value;
    getSpeelSchema();
  },
  minCharacters: 3
});

$("#eigenTeams").dropdown({
  apiSettings: {
    url: "nevobo-search.php?q={query}"
  },
  onChange: function(value, text, $selectedItem) {
    eigenTeamIds = [];
    if (value) {
      eigenTeamIds = value.split(",");
    }

    getSpeelSchema();
  },
  minCharacters: 3
});

var getSpeelSchema = function() {
  var url = "GetSpeelschemas.php?coachteamId=" + coachTeamId;
  for (eigenTeamId of eigenTeamIds) {
    url += "&eigenTeamIds[]=" + eigenTeamId;
  }
  $.get(url, function(data) {
    var coachoverzicht = JSON.parse(data);
    createTable(coachoverzicht);
    createSamenvatting(coachoverzicht.samenvatting);
  });
};

function createSamenvatting(samenvatting) {
  var samenvattingDiv = document.getElementById("samenvatting");
  while (samenvattingDiv.firstChild) {
    samenvattingDiv.removeChild(samenvattingDiv.firstChild);
  }
  addButton(samenvattingDiv, "btn btn-success", samenvatting.yes);
  addButton(samenvattingDiv, "btn btn-warning", samenvatting.maybe);
  addButton(samenvattingDiv, "btn btn-danger", samenvatting.no);
}

function addButton(source, badgeClass, text) {
  var span = document.createElement("button");
  span.setAttribute("class", badgeClass);
  span.appendChild(document.createTextNode(text));
  source.appendChild(span);
  source.appendChild(document.createTextNode(" "));
}

var createTable = function(coachoverzicht) {
  var coachoverzichtDiv = document.getElementById("coachoverzicht");
  while (coachoverzichtDiv.firstChild) {
    coachoverzichtDiv.removeChild(coachoverzichtDiv.firstChild);
  }

  var table = document.createElement("table");
  table.setAttribute("class", "table");

  var tableBody = document.createElement("tbody");
  for (dag of coachoverzicht.dagen) {
    var tr = document.createElement("tr");
    addCellToRow(tr, dag.datum);
    tableBody.appendChild(tr);

    var tr = document.createElement("tr");
    tr.setAttribute("class", getClassFromDag(dag));
    var td = document.createElement("td");
    addWedstrijdenToCell(td, dag.wedstrijden);
    tr.appendChild(td);

    tableBody.appendChild(tr);
  }
  table.appendChild(tableBody);
  coachoverzichtDiv.appendChild(table);
};

var addCellToRow = function(tr, text) {
  var td = document.createElement("td");
  var datum = document.createElement("div");
  datum.setAttribute("class", "mx-auto");
  datum.setAttribute("style", "width: 200px;");
  datum.appendChild(document.createTextNode(text));
  td.appendChild(datum);
  tr.appendChild(td);
};

var addWedstrijdenToCell = function(td, wedstrijden) {
  for (wedstrijd of wedstrijden) {
    var span = document.createElement("span");
    var spanClass = wedstrijd.isCoachteam
      ? "badge badge-primary"
      : "badge badge-light";
    span.setAttribute("class", spanClass);
    var text = wedstrijd.team1 + " - " + wedstrijd.team2;
    if (wedstrijd.timestamp) {
      var timestamp = new Date(wedstrijd.timestamp.date);
      text +=
        " @ " +
        timestamp.getHours() +
        ":" +
        ("0" + timestamp.getMinutes()).substr(-2);
    }

    span.appendChild(document.createTextNode(text));
    var h4 = document.createElement("h4");
    h4.appendChild(span);
    var div = document.createElement("div");
    var divClass = wedstrijd.isCoachteam ? "float-left" : "float-right";
    divClass += " m-2";
    div.setAttribute("class", divClass);
    div.appendChild(h4);
    td.appendChild(div);
    td.appendChild(document.createTextNode("\n"));
  }
};

function getClassFromDag(dag) {
  switch (dag.isCoachingPossible) {
    case "yes":
      return "table-success";
    case "maybe":
      return "table-warning";
    case "no":
      return "table-danger";
    default:
      return "table-Secondary";
  }
}
