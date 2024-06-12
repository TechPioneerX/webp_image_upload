<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
  $uploadDir = 'uploads/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  $fileTmpPath = $_FILES['image']['tmp_name'];
  $fileName = $_FILES['image']['name'];
  $fileSize = $_FILES['image']['size'];
  $fileType = $_FILES['image']['type'];
  $fileNameCmps = explode(".", $fileName);
  $fileExtension = strtolower(end($fileNameCmps));

  $allowedFileExtensions = ['jpg', 'jpeg', 'png', 'webp'];

  if (in_array($fileExtension, $allowedFileExtensions)) {
    try {
      $randomPrefix = bin2hex(random_bytes(8));
    } catch (Exception $e) {
      echo "ランダムプレフィックスの生成中にエラーが発生しました: " . $e->getMessage();
      exit;
    }
    $newFileName = $randomPrefix . '_' . basename($fileName);
    $uploadFileDir = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $uploadFileDir)) {
      try {
        $originalPath = $uploadFileDir;
        $webpSmallPath = $uploadDir . pathinfo($newFileName, PATHINFO_FILENAME) . '_small.webp';

        convertToWebP($originalPath, $webpSmallPath, true);

        echo "ファイルは正常にアップロードされました。<br>";
        echo "オリジナル画像: <a href='$originalPath' target='_blank'>$newFileName</a><br>";
        echo "縮小されたWebP画像: <a href='$webpSmallPath' target='_blank'>" . basename($webpSmallPath) . "</a><br>";
      } catch (Exception $e) {
        echo "画像の変換中にエラーが発生しました: " . $e->getMessage();
      }
    } else {
      echo "アップロードされたファイルの移動中にエラーが発生しました。";
    }
  } else {
    echo "アップロードに失敗しました。許可されているファイルタイプ: " . implode(',', $allowedFileExtensions);
  }
}

/**
 * @throws Exception
 */
function convertToWebP($filePath, $outputPath, $reduceSize = false, $quality = 80)
{
  $info = getimagesize($filePath);
  $mime = $info['mime'];

  switch ($mime) {
    case 'image/jpeg':
      $image = imagecreatefromjpeg($filePath);
      break;
    case 'image/png':
      $image = imagecreatefrompng($filePath);
      break;
    case 'image/webp':
      $image = imagecreatefromwebp($filePath);
      break;
    default:
      throw new Exception('サポートされていない画像フォーマットです');
  }

  if ($reduceSize) {
    $width = imagesx($image) / 2;
    $height = imagesy($image) / 2;
    $reducedImage = imagescale($image, $width, $height);
    imagewebp($reducedImage, $outputPath, $quality);
    imagedestroy($reducedImage);
  } else {
    imagewebp($image, $outputPath, $quality);
  }

  imagedestroy($image);
}
