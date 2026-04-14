<?php
session_start();

/* إذا المستخدم مسجل دخول بالفعل */
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>مرحبًا بك</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>
*{
  box-sizing:border-box;
  font-family:'Cairo',sans-serif;
}

body{
  margin:0;
  height:100vh;
  background: linear-gradient(135deg, #0f172a, #1e293b);
  display:flex;
  justify-content:center;
  align-items:center;
  color:#fff;
}

/* شريط علوي */
.top-bar{
  position:fixed;
  top:0;
  width:100%;
  height:3px;
  background:linear-gradient(90deg,#38bdf8,#6366f1,#38bdf8);
}

/* الكرت الرئيسي */
.card{
  width:900px;
  background:#ffffff;
  border-radius:18px;
  display:flex;
  overflow:hidden;
  box-shadow:0 25px 60px rgba(0,0,0,.35);
}

/* القسم النصي */
.card-content{
  width:55%;
  padding:55px 45px;
  color:#0f172a;
}

.card-content h1{
  margin:0 0 15px;
  font-size:32px;
  color:#0f172a;
}

.card-content p{
  margin:0 0 30px;
  line-height:1.9;
  font-size:15px;
  color:#475569;
}

/* الأزرار */
.buttons{
  display:flex;
  gap:12px;
}

button{
  flex:1;
  padding:14px;
  border:none;
  border-radius:10px;
  cursor:pointer;
  font-size:14px;
  font-weight:700;
  transition:.25s;
}

.btn-login{
  background:linear-gradient(135deg,#6366f1,#3b82f6);
  color:white;
}

.btn-register{
  background:#0f172a;
  color:white;
}

button:hover{
  transform:translateY(-2px);
  opacity:.9;
}

/* الصورة */
.card-image{
  width:45%;
  background:
    linear-gradient(180deg,rgba(15,23,42,.3),rgba(15,23,42,.9)),
    url("../Files/Database/assets/images/resume2.jpg");
  background-size:cover;
  background-position:center;
  display:flex;
  align-items:flex-end;
  padding:30px;
  font-size:14px;
  line-height:1.8;
}

/* موبايل */
@media (max-width:768px){
  .card{
    flex-direction:column;
    width:92%;
  }
  .card-content, .card-image{
    width:100%;
  }
  .card-image{
    height:200px;
  }
  .buttons{
    flex-direction:column;
  }
}
</style>
</head>

<body>

<div class="top-bar"></div>

<div class="card">

  <div class="card-content">
    <h1>أنشئ سيرتك الذاتية باحترافية ✨</h1>

    <p>
      منصتنا تساعدك على إنشاء سيرة ذاتية حديثة ومنظمة خلال دقائق.
      اختر التصميم المناسب، أضف بياناتك، وصدّر سيرتك بسهولة.
      كل ما تحتاجه للتميز في سوق العمل في مكان واحد.
    </p>

    <div class="buttons">
      <button class="btn-login" onclick="location.href='login.php'">
        تسجيل الدخول
      </button>

      <button class="btn-register" onclick="location.href='register.php'">
        إنشاء حساب
      </button>
    </div>
  </div>

  <div class="card-image">
    ابدأ الآن واصنع سيرة ذاتية تعكس مهاراتك وخبراتك بشكل احترافي.
  </div>

</div>

</body>
</html>
