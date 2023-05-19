# Member-serial-number-code-system

這是一套簡易的序號儲值系統；可供其他開發人員應用串接(提供了API) ，可廣泛應用於(替)應用程式並加上付費機制等會員系統，但始終還是無法媲美外面的商店系統機制:( ，這只是因剛好閒暇時而作出來的。

![image](https://i.ibb.co/7N64DDy/images.gif)

## 更新

會因不同問題而有不一定更新時間（例：**代碼有重大安全問題等等諸如此類(就是立即)**....) ，日後也會視情況增加其他功能(如果了解程式碼架構也可自由發揮)

## 注意

系統當初在開發的時候再API地方或者其他小細節還藏著許多小問題要debug，**並不建議直接應用在上市產品**；除非你了解架構(各個風險)。

## 系統語言(支援)

PHP 至少7.0版本以上(**建議7.4以上**)

MySQL

## 安全

這套系統在撰寫代碼時以及公開之前作者本身就先已經測試各種基本安全漏洞問題，但可能還是無法避免有其他安全問題（如果有，請你報告讓我知道）；程式碼也沒有埋後門，如果還是**無法放心(請勿使用)**　。

## 如何使用？

後台管理員預設帳號為admin/admin (目錄中有個pwd_test.php，這是將密碼hash過的代碼 => 如果你想直接變更管理者密碼可透過後台或該檔案進行SQL 操作) 

後台管理(管理使用者、管理序號代碼)頁面為："**admanage_codes.php**","**admanage_users.php**" 

以下為使用步驟：

1.更改目錄中兩個database (**public**,**pbulic/api/**) => 這兩個都是資料庫連結設定，至於API目錄中的是API 查詢與資料庫作互動（你也可直接修改**api.php** 中的"**require_once 'database.php';**" 可選擇直接應用public 底下

2.將"Login.php" 以及"Register.php" 程式碼中的"**$secretKey = "recaptcha key";** 該行位置替換為你在Google Recaptcha 申請的密鑰 => https://www.google.com/recaptcha/about/

3.將"**API**"目錄中的api.php 該行程式碼(**define('SECRET_KEY', 'Your Secret Key');**) 替換你想為自己的api jwt 設置的密鑰（也可直接透過public目錄中有一個名為"**jwtkey.php**" 產生，請避免將此Key 洩漏給其他人

4.　↑ 以上完成（大功告成）。

## API 調用
### 1. 登入
* URL: /api/login (http://exmaple.com/api/login)
* Method： POST
* Headers： Content-Type: application/json
* Body：username,password
  > {
     "username": "<username>",
     "password": "<password>"
     }

* Susccess Response: JWT token 在 body 中返回。200 OK => {jwt}
* Failed   Response: 如果 username 或 password 為空或缺失，返回 400 Bad Request。如果帳號或密碼錯誤，返回 401 Unauthorized。
     
### 2. 註冊
* URL: /api/register (http://exmaple.com/api/register)
* Method： POST
* Headers： Content-Type: application/json
* Body：username,password
  > {
     "username": "<username>",
     "password": "<password>"
     }

* Susccess Response: 200 OK => {success:true}
* Failed   Response: 如果使用者已存在，返回 400 Bad Request。如果發生服務器錯誤，返回 500 Internal Server Error。
  
 ### 3. 驗證序號
* URL: /api/submit_code (http://exmaple.com/api/submit_code)
* Method： POST
* Headers: Content-Type: application/json, Authorization: Bearer jwt token
* Body：username,code
  > {
     "username": "<username>",
     "code": "<????>"
     }

* Susccess Response: 200 OK => {success:true}
* Failed   Response: 如果 JWT token 缺失或無效，返回 401 Unauthorized。如果沒有足夠的權限，返回 403 Forbidden。如果序號無效或已被使用，返回 400 Bad Request。
  
 ### 4. 獲取用戶狀態
* URL: /api/user/{username}/status (http://exmaple.com/api/api/user/{username}/status)
* Method： GET
* Headers： Content-Type: application/json
* Susccess Response: 返回使用者的狀態，即 active 或 inactive。200 OK
* Failed   Response: 如果使用者不存在，返回 404 Not Found。
  
 ### 5. 獲取用戶有效日期
* URL: /api/user/{username}/expiration (http://exmaple.com/api/user/{username}/expiration)
* Method： GET
* Headers： Content-Type: application/json
* Susccess Response: 返回使用者的過期日期或為空。200 OK
* Failed   Response: 如果使用者不存在，返回 404 Not Found。

## 常見問題
  
  
* 我發現系統有安全問題，可以通報嗎 ? 

    → 『可以，非常歡迎！詳細技術細節寫信給我:honcbb@gmail.com』
  
 * 為什麼我發現有些地方（譬API) 有直接噴debug http 問題 ? 

    → 『因為作者有漏掉某些地方的判斷(提示)等等....，若您了解程式碼架構也可自行修正，或者拉project issues report 或寫信給我:honcbb@gmail.com』
  
 * 這套儲值序號系統我能直接應用在我自家產品上嗎 ? 

    → 『上述所說＂並不建議＂，因為系統在開發有藏了很多小bug 細節未處理到（可能會造成其他錯誤問題），除非你了解整個程式碼應用架構可自行修正並斟酌應用』
  
 * API 是否還可以增加其他功能 ? 

    → 『日後有閒暇之餘會在更新，您了解API架構也可自行編寫應用』
  
 ↑ 最後更新日期: 2023/05/19

