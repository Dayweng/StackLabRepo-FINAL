<?php
//#region LOGOUT
session_start();
session_destroy();
header('Location: ../login.html');
exit;
//#endregion
