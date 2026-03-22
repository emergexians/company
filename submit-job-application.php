<?php
// submit-job-application.php

session_start();

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/
$host = "localhost";
$dbname = "your_database_name";
$username = "your_database_username";
$password = "your_database_password";

/*
|--------------------------------------------------------------------------
| Upload Configuration
|--------------------------------------------------------------------------
*/
$uploadDir = __DIR__ . '/uploads/resumes/';
$allowedExtensions = ['pdf', 'doc', 'docx'];
$maxFileSize = 5 * 1024 * 1024; // 5 MB

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/
function clean_input(string $value): string
{
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

/*
|--------------------------------------------------------------------------
| Create Upload Folder If Not Exists
|--------------------------------------------------------------------------
*/
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/*
|--------------------------------------------------------------------------
| Only Allow POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

/*
|--------------------------------------------------------------------------
| Collect and Validate Form Fields
|--------------------------------------------------------------------------
*/
$full_name  = clean_input($_POST['full_name'] ?? '');
$email      = clean_input($_POST['email'] ?? '');
$phone      = clean_input($_POST['phone'] ?? '');
$position   = clean_input($_POST['position'] ?? '');
$experience = clean_input($_POST['experience'] ?? '');
$location   = clean_input($_POST['location'] ?? '');
$salary     = clean_input($_POST['salary'] ?? '');
$portfolio  = clean_input($_POST['portfolio'] ?? '');
$skills     = clean_input($_POST['skills'] ?? '');
$message    = clean_input($_POST['message'] ?? '');

$errors = [];

if ($full_name === '') {
    $errors[] = "Full name is required.";
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required.";
}

if ($phone === '') {
    $errors[] = "Phone number is required.";
}

if ($position === '') {
    $errors[] = "Position is required.";
}

if ($experience === '') {
    $errors[] = "Experience is required.";
}

if ($location === '') {
    $errors[] = "Location is required.";
}

/*
|--------------------------------------------------------------------------
| Resume Upload Validation
|--------------------------------------------------------------------------
*/
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Resume upload is required.";
} else {
    $fileName = $_FILES['resume']['name'];
    $fileTmpPath = $_FILES['resume']['tmp_name'];
    $fileSize = $_FILES['resume']['size'];

    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions, true)) {
        $errors[] = "Only PDF, DOC, and DOCX files are allowed.";
    }

    if ($fileSize > $maxFileSize) {
        $errors[] = "Resume file size must be less than 5 MB.";
    }
}

/*
|--------------------------------------------------------------------------
| Stop If Validation Fails
|--------------------------------------------------------------------------
*/
if (!empty($errors)) {
    echo "<h2>Application Submission Failed</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . $error . "</li>";
    }
    echo "</ul>";
    echo '<a href="job-application.html">Go Back</a>';
    exit;
}

/*
|--------------------------------------------------------------------------
| Generate Safe Resume Filename
|--------------------------------------------------------------------------
*/
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
$newFileName = time() . '_' . $safeName . '.' . $fileExtension;
$destination = $uploadDir . $newFileName;

/*
|--------------------------------------------------------------------------
| Move Uploaded File
|--------------------------------------------------------------------------
*/
if (!move_uploaded_file($fileTmpPath, $destination)) {
    die("Failed to upload resume.");
}

/*
|--------------------------------------------------------------------------
| Save Data to Database
|--------------------------------------------------------------------------
*/
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $sql = "INSERT INTO job_applications (
                full_name,
                email,
                phone,
                position,
                experience,
                location,
                salary,
                portfolio,
                skills,
                message,
                resume_file,
                created_at
            ) VALUES (
                :full_name,
                :email,
                :phone,
                :position,
                :experience,
                :location,
                :salary,
                :portfolio,
                :skills,
                :message,
                :resume_file,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':full_name'   => $full_name,
        ':email'       => $email,
        ':phone'       => $phone,
        ':position'    => $position,
        ':experience'  => $experience,
        ':location'    => $location,
        ':salary'      => $salary,
        ':portfolio'   => $portfolio,
        ':skills'      => $skills,
        ':message'     => $message,
        ':resume_file' => $newFileName
    ]);

} catch (PDOException $e) {
    // delete uploaded file if db save fails
    if (file_exists($destination)) {
        unlink($destination);
    }
    die("Database error: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted | Emergexians</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #071120 0%, #12233f 45%, #6083be 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .success-box {
            max-width: 650px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            text-align: center;
        }
        .success-icon {
            font-size: 60px;
            color: #6083be;
            margin-bottom: 20px;
        }
        .btn-main {
            background: #6083be;
            color: #fff;
            border-radius: 10px;
            padding: 12px 24px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        .btn-main:hover {
            background: #31558f;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h2 class="mb-3">Application Submitted Successfully</h2>
        <p class="text-muted mb-3">
            Thank you, <?php echo $full_name; ?>. Your application has been submitted successfully.
            Our team will review your profile and contact you if your application matches our current requirements.
        </p>
        <a href="careers.html" class="btn-main">Back to Careers</a>
    </div>
</body>
</html>



<!----------------------Database table------------------------>


<!----------------
CREATE TABLE job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    position VARCHAR(255) NOT NULL,
    experience VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    salary VARCHAR(100) DEFAULT NULL,
    portfolio VARCHAR(500) DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    message TEXT DEFAULT NULL,
    resume_file VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);
------------>

<!----------------------Database table------------------------>