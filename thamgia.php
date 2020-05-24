
<?php
$ID = $_POST['ID']; // lấy id từ chatfuel
$gioitinh = $_POST['gt'];// lấy giới tính

require_once 'config.php'; //lấy thông tin từ config
$conn = mysqli_connect($DBHOST, $DBUSER, $DBPW, $DBNAME); // kết nối data
////// Hàm Gửi JSON //////////

function request($userid,$jsondata) { 
  global $TOKEN;
  global $BOT_ID;
  global $BLOCK_NAME;
  $url = "https://api.chatfuel.com/bots/$BOT_ID/users/$userid/send?chatfuel_token=$TOKEN&chatfuel_block_name=$BLOCK_NAME";
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_exec($ch);
  $errorChat = '{
     "messages": [
    {
      "attachment":{
        "type":"template",
        "payload":{
          "template_type":"generic",
          "elements":[
            {
              "title":"Lỗi !!!",
              "subtitle":"Đã xảy ra lỗi gửi tin. Bạn gửi lại thử nhé."
            }
          ]
        }
      }
    }
  ]
} ';
	if (curl_errno($ch)) {
		echo errorChat;
	} else {
		$resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($resultStatus == 200) {
			// send ok
		} else {
			echo errorChat;
		}
	}
	curl_close($ch);

  
}
///// Hàm gửi tin nhắn //////////

function sendchat($userid,$noidung){
global $JSON;
$payload = '{"'.$JSON.'":"'.$noidung.'"}';
request($userid,$payload);		
}


///// hàm kiểm tra hàng chờ ///////
function hangcho($userid) {
  global $conn;

  $result = mysqli_query($conn, "SELECT `hangcho` from `users` WHERE `ID` = $userid");
  $row = mysqli_fetch_assoc($result);

  return intval($row['hangcho']) !== 0;
}

//// Kết nối hai người /////
function addketnoi($user1, $user2) {
  global $conn;

  mysqli_query($conn, "UPDATE `users` SET `trangthai` = 1, `ketnoi` = $user2, `hangcho` = 0 WHERE `ID` = $user1");
  mysqli_query($conn, "UPDATE `users` SET `trangthai` = 1, `ketnoi` = $user1, `hangcho` = 0 WHERE `ID` = $user2");
}
/////Tìm kiếm kết nối /////

function ketnoi($userid,$gioitinh) { //tìm người chát 
  global $conn;
  
  //tìm đối tượng ngẫu nhiên
  $result = mysqli_query($conn, "SELECT `ID` FROM `users` WHERE `ID` != $userid AND `hangcho` = 1  AND `ID` NOT IN (SELECT `idBlocked` FROM `block` WHERE `idBlock` = $userid) LIMIT 1");
  $row = mysqli_fetch_assoc($result);
  $partner = $row['ID'];
  // xử lý kiểm tra
  if ($partner == 0) { // nếu người không có ai trong hàng chờ
  mysqli_query($conn, "UPDATE `users` SET `hangcho` = 1 WHERE `ID` = $userid"); 
     echo'{
     "messages": [
    {
      "attachment":{
        "type":"template",
        "payload":{
          "template_type":"generic",
          "elements":[
            {
              "title":"Đang thả câu...",
              "subtitle":"Đợi xíu ha, BOT đang tìm một con cá siu to khổng lồ cho bạn nè (😋)"
            }
          ]
        }
      }
    }
  ]
} ';
	  
} else {  // neu co nguoi trong hàng chờ
    addketnoi($userid, $partner);
	
	sendchat($userid,"✅ Lẹ lên, lẹ lên, cá đớp thính rồi (😝)");  
	sendchat($partner,"✅ Lẹ lên, lẹ lên, cá đớp thính rồi (😝)");  
  }
}

//////// LẤY ID NGƯỜI CHÁT CÙNG ////////////
function getRelationship($userid) {
  global $conn;

  $result = mysqli_query($conn, "SELECT `ketnoi` from `users` WHERE `ID` = $userid");
  $row = mysqli_fetch_assoc($result);
  $relationship = $row['ketnoi'];
  return $relationship;
}

//// hàm kiểm tra trạng thái
function trangthai($userid) {
  global $conn;

  $result = mysqli_query($conn, "SELECT `trangthai` from `users` WHERE `ID` = $userid");
  $row = mysqli_fetch_assoc($result);

  return intval($row['trangthai']) !== 0;
}

//// Xử lý //////
if (!trangthai($ID)){// nếu chưa chát
if (!hangcho($ID)) { // nếu chưa trong hàng chờ
ketnoi($ID,$gioitinh);
}else{
echo'{
 "messages": [
    {
      "attachment":{
        "type":"template",
        "payload":{
          "template_type":"generic",
          "elements":[
            {
              "title":"Đang thả câu...",
              "subtitle":"Chưa có cá nào dính thính đâu. Bạn chờ chút nhé! "
            }
          ]
        }
      }
    }
  ]
}';
}
}else{
// khi đang chát ! giải quyết sau !!
echo'{
 "messages": [
    {
      "attachment":{
        "type":"template",
        "payload":{
          "template_type":"generic",
          "elements":[
            {
              "title":"Cảnh báo",
              "subtitle":"Bạn đang được kết nối với cá rồi ! Hãy gõ \'End\' để thoát"
            }
          ]
        }
      }
    }
  ]
}';
}
mysqli_close($conn);
?>
