<?php
$DB_FILENAME="/var/database/myCMS.db";
$db = new SQLite3($DB_FILENAME);

if (!empty($_POST)) {
  if ( !empty($_POST["action"]) ) {
    switch ($_POST["action"]) {
      case "loadtopic":
        $htmlstr="<option value=''>----</option> ";
        $results = $db->query("SELECT topicID, topic FROM topic");
        while ($row = $results->fetchArray()) {
          $htmlstr=$htmlstr."<option value=".$row[0].">".$row[1]."(".$row[0].")"."</option> ";
        }
        echo $htmlstr;
      break;
      case "loadversion":
        if ( !empty($_POST["topic"]) ) {
          $mytopic=$_POST["topic"];
          $results = $db->query("SELECT  ifnull(max(version),1) from doku where topicID = ".$mytopic);
          $row = $results->fetchArray();
          echo $row[0];
        } else {
          echo "0";
        }
      break;
      case "loadversionselect":
        if ( !empty($_POST["topic_del"]) ) {
          $htmlstr="<option value=''>--</option> ";
          $results = $db->query("SELECT distinct version FROM doku where topicid=".$_POST["topic_del"]);
          while ($row = $results->fetchArray()) {
            $htmlstr=$htmlstr."<option value=".$row[0].">".$row[0]."</option> ";
          }
          echo $htmlstr;
        }
      break;
      case "loadtext":
        if (!empty($_POST["topic"]) && !empty($_POST["version"]) ) {
          $mytopic=$_POST["topic"];
          $myversion=$_POST["version"];          
          $htmlstr="";
          $results = $db->query("SELECT text, seq from doku where topicID = $mytopic and version = $myversion order by seq");
          while ($row = $results->fetchArray()) {
            $htmlstr=$htmlstr.$row[0];
          }
          echo $htmlstr;
        }
      break;
      case "savetext":
        if (!empty($_POST["topic"]) && !empty($_POST["version"]) && !empty($_POST["text"]) ) {
          $mytopic=$_POST["topic"];
          $myversion=$_POST["version"];          
          $mytext=$_POST["text"]; 
          $sql = "delete from doku where topicID = $mytopic and version = $myversion ";
          if ( $db->exec($sql) ) {
            $stmt = $db->prepare('insert into doku ( topicID, version, text, seq) values ( :topic, :version, :text, :seq)');
            $stmt->bindParam(':topic', $mytopic, SQLITE3_INTEGER);
            $stmt->bindParam(':version', $myversion, SQLITE3_INTEGER);
            $stmt->bindParam(':text', $mytextpart, SQLITE3_TEXT);
            $stmt->bindParam(':seq', $myseq, SQLITE3_INTEGER);
            $mytextlen=strlen($mytext);
            $myseq=1; 
            for ($i=0; $i < $mytextlen; $i=$i+1024) {
              $mytextpart=substr($mytext,$i,1024);
              $stmt->execute();
              $myseq++;
            }  
            $stmt->close(); 
          } else {
            echo "$sql nicht ausgefuehrt!";
          }
          echo "Gespeichert";
        } else {
          echo "Speicherfehler";
        }
      break;
      case "newtopic":
        if ( !empty($_POST["newtopic"]) ) {
          $mynewtopic=$_POST["newtopic"];
          $stmt = $db->prepare("insert into topic (topicID, topic) values ( (select ifnull(max(topicID),0)+1 from topic), :topic) ");
          $stmt->bindParam(':topic', $mynewtopic, SQLITE3_TEXT);
          $stmt->execute();
          $stmt->close();         
          $sql = "insert into doku(topicID, version, text, seq) values((select topicID from topic where topic='$mynewtopic'),1,' ',1) ";
          $db->exec($sql);
        }
        $htmlstr="<option value=''>----</option> ";
        $results = $db->query("SELECT topicID, topic FROM topic");
        while ($row = $results->fetchArray()) {
          $htmlstr=$htmlstr."<option value=".$row[0].">".$row[1]."(".$row[0].")"."</option> ";
        }
        echo $htmlstr;
      break;  
      case "delete":
        if (!empty($_POST["topic_del"]) && !empty($_POST["version_del"]) ) {
          $mytopic=$_POST["topic_del"];
          $myversion=$_POST["version_del"];
          $sql="delete from doku where topicID=$mytopic and version=$myversion";
          $db->exec($sql);
          $results = $db->query("SELECT count(*) from doku where topicID=$mytopic ");
          $row = $results->fetchArray();
          if ( $row[0] == 0 ) {
            $sql="delete from topic where topicID=$mytopic";
            $db->exec($sql);
          }
          $htmlstr="<option value=''>----</option> ";
          $results = $db->query("SELECT topicID, topic FROM topic");
          while ($row = $results->fetchArray()) {
            $htmlstr=$htmlstr."<option value=".$row[0].">".$row[1]."(".$row[0].")"."</option> ";
          }
          echo $htmlstr;
        }
      break;
    }
  }
}
$db->close();

?>

