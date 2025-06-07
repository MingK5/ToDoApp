<?php
session_start();
session_regenerate_id(true);

$_SESSION = [];
session_destroy();

// kill the cookie
setcookie(
  session_name(), 
  '', 
  time() - 3600, 
  '/ToDoApp/',   // exactly the same path as your app
  '',            // domain (leave blank if you didn’t set one)
  false,         // secure-only?
  true           // HttpOnly?
);

header('Location: /ToDoApp/');
exit;
