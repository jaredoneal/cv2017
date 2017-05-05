<?php
  $lifetime = 60 * 60 * 24 * 3;
  session_set_cookie_params($lifetime, "/");
  session_start();
  require('../model/database.php');
  require "../model/accounts_db.php";
  require "../model/courses_db.php";
  require "../model/cart_db.php";
  include "partials/globalVars.php";

  if (!isset ($_SESSION['firstLoad'])) {
    // Initialize errors
    $_SESSION['usernameError'] = "";
    $_SESSION['passwordError'] = "";
    $_SESSION['nameError'] = "";
    $_SESSION['emailError'] = "";

    $_SESSION['firstLoad'] = 'false';

    // Initialize cart
    $_SESSION['course_cart'] = array();
  }

  // Initialize regular variables
  $addedSuccessful = false;

  $action = filter_input(INPUT_POST, 'action');
  if ($action == NULL) {
      $action = filter_input(INPUT_GET, 'action');
      if ($action == NULL) {
          $action = 'display_login';
      }
  }

  switch ($action) {
    case 'display_login':
      $_SESSION['usernameError'] = "";
      $_SESSION['passwordError'] = "";
      header("Location: $loginPage");
      break;

    case 'login':
      $username = filter_input(INPUT_POST, 'username');
      $password = filter_input(INPUT_POST, 'password');
      $account_credentials = validate_account($username);

      if ($username == '') {
        $_SESSION['usernameError'] = "Please enter a username.";
      } else if (get_user($username) == '') {
        $_SESSION['usernameError'] = "Username does not exist.";
      } else {
        $_SESSION['usernameError'] = "";
      }

      if ($password == '') {
        $_SESSION['passwordError'] = "Please enter a password.";
      } else if ($account_credentials['password'] != $password) {
        $_SESSION['passwordError'] = "Incorrect password.";
      } else {
        $_SESSION['passwordError'] = "";
      }

      if ($_SESSION['usernameError'] == "" && $_SESSION['passwordError'] == "") {
        $_SESSION['is_valid_user'] = 'true';
        $_SESSION['current_user'] = $username;
        $_SESSION['course_cart'] = array();
        header("Location: $homePage");
      } else {
        header("Location: $loginPage");
      }
      break;
    case 'display_signup':
      $_SESSION['nameError'] = "";
      $_SESSION['usernameError'] = "";
      $_SESSION['emailError'] = "";
      $_SESSION['passwordError'] = "";
      header("Location: $signupPage");
      break;

    case 'signup':
      $name = filter_input(INPUT_POST, 'name');
      $school = filter_input(INPUT_POST, 'school');
      $username = filter_input(INPUT_POST, 'username');
      $email = filter_input(INPUT_POST, 'email');
      $password = filter_input(INPUT_POST, 'password');

      if ($name == '') {
        $_SESSION['nameError'] = "Please enter a name.";
      } else { $_SESSION['nameError'] = ""; }

      if ($username == '') {
        $_SESSION['usernameError'] = "Please enter a username.";
      } else if (get_user($username) != "") {
        $_SESSION['usernameError'] = "Username already exists.";
      } else { $_SESSION['usernameError'] = ""; }

      if ($email == '') {
        $_SESSION['emailError'] = "Please enter a email.";
      } else { $_SESSION['emailError'] = ""; }

      if ($password == '') {
        $_SESSION['passwordError'] = "Please enter a password.";
      } else { $_SESSION['passwordError'] = ""; }

      if ($_SESSION['nameError'] == "" && $_SESSION['usernameError'] == "" &&
          $_SESSION['emailError'] == "" && $_SESSION['passwordError'] == "" &&
          get_user($username) == "") {

        add_account($name, $school, $username, $email, $password);
        $_SESSION['is_valid_user'] = 'true';
        $_SESSION['current_user'] = $username;
        $_SESSION['course_cart'] = array();
        header("Location: $homePage");
      } else {
        header("Location: $signupPage");
      }
      break;

    case 'search_courses':
      $searchInput = filter_input(INPUT_POST, 'search_input');
      header("Location: $resultsPage?searchInput=$searchInput");

      break;

    case 'display_course_details':
      $courseID = filter_input(INPUT_GET, 'courseID');

      if ($courseID != "") {
        //$_SESSION['selected_course'] = get_course_details($courseID);
        header("Location: $detailsPage?courseID=$courseID");
      }
      break;

    case 'display_settings':
      if ($_SESSION['is_valid_user'] == 'true') {
        header("Location: $settingsPage");
      } else {
        header('Location: .');
      }
      break;

    case 'display_accounts':
      header("Location: $accountsPage");
      break;

    case 'display_settings_from_user':
      $user = filter_input(INPUT_POST, 'user');
      header("Location: $settingsPage?user=$user");
      break;

    case 'delete_user':
      $user = filter_input(INPUT_POST, 'user');
      delete_user($user);
      header("Location: $accountsPage");
      break;

    case 'update_course':
      $CRN = filter_input(INPUT_POST, 'crn');
      $subject = filter_input(INPUT_POST, 'subject');
      $course = filter_input(INPUT_POST, 'course');
      $credits = filter_input(INPUT_POST, 'credits');
      $title = filter_input(INPUT_POST, 'title');
      $days = filter_input(INPUT_POST, 'days');
      $startTime = filter_input(INPUT_POST, 'startTime');
      $endTime = filter_input(INPUT_POST, 'endTime');
      $location = filter_input(INPUT_POST, 'location');
      $teacherID = filter_input(INPUT_POST, 'teacherID');
      $coursestaughtID = filter_input(INPUT_POST, 'courseID');
      $firstName = filter_input(INPUT_POST, 'firstName');
      $middleName = filter_input(INPUT_POST, 'middleName');
      $lastName = filter_input(INPUT_POST, 'lastName');

      if ($CRN == NULL || $subject == NULL || $course == NULL || $credits == NULL || $title == NULL ||
        $days == NULL || $startTime == NULL || $endTime == NULL || $location == NULL || $teacherID == NULL ||
        $coursestaughtID == NULL || $firstName == NULL || $lastName == NULL) {
          printf('Error while updating course. Please go back and fill in any blank fields.');
      } else {
        if ($middleName == NULL) {
          $middleName = "";
        }
        update_course($CRN, $subject, $course,
          $credits, $title, $days, $startTime, $endTime, $location, $teacherID, $coursestaughtID,
          $firstName, $middleName, $lastName);
        header("Location: $detailsPage?courseID=$coursestaughtID");
      }
      break;

    case 'delete_course':
      $courseID = filter_input(INPUT_GET, 'courseID');
      $CRN = get_crn($courseID);
      delete_course($CRN['CRN']);
      header("Location: $resultsPage");
      break;

    case 'add_to_cart':
      $courseID = filter_input(INPUT_GET, "coursesTaughtID");
      if (in_array($courseID, $_SESSION['course_cart'])) {
          echo $courseID." already exists.";
          break;
      }
      array_push($_SESSION['course_cart'], $courseID);
      $addedSuccessful = true;
      header("Location: $detailsPage");
      break;

    case 'readd_to_cart':
      $courseID = filter_input(INPUT_GET, "coursesTaughtID");
      array_push($_SESSION['course_cart'], $courseID);
      header("Location: $cartPage");
      break;

    case 'delete_course':
      $courseID = filter_input(INPUT_GET, "id");
      $index = array_search($courseID, $_SESSION['course_cart']);
      unset($_SESSION['course_cart'][$index]);
      header("Location: $cartPage");
      break;

    case 'logout':
      $_SESSION = array();
      session_destroy();
      $name = session_name();
      $expire = strtotime('-1 year');
      $params = session_get_cookie_params();
      $path = $params['path'];
      $domain = $params['domain'];
      $secure = $params['secure'];
      $httponly = $params['httponly'];
      setcookie($name, '', $expire, $path, $domain, $secure, $httponly);
      header("location: .");
      break;

    case 'checkout':
      $courseIDs = get_registered_course_ids();
      $registeredCourseIDs = array();
      foreach ($courseIDs as $courseID) {
        $coursesTaughtID = get_courses_taught_id($courseID['CRN']);
        array_push($registeredCourseIDs, $coursesTaughtID['coursesTaughtID']);
      }
      if (!empty($_SESSION['course_cart'])) {
        $creationDate = date('Y-m-d');
        foreach ($_SESSION['course_cart'] as $item) {
          if (!in_array($item, $registeredCourseIDs)) {
            addToCart($_SESSION['current_user'], $item, $creationDate);
          }
          $index = array_search($item, $_SESSION['course_cart']);
          unset($_SESSION['course_cart'][$index]);
        }
        header("Location: $confirmationPage");
      } else {
        header("Location: $cartPage");
      }
      break;
  }

?>
