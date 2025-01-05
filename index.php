<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $targetDir = "uploads/";
    $originalFileName = $_FILES["image"]["name"];
    $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
    $newFileName = date("Y-m-d") . '_' . substr(md5(rand()), 0, 5) . '.' . $fileExtension;
    $targetFile = $targetDir . $newFileName;
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        addWatermark($targetFile);

        $uploadSuccess = true;
    } else {
        $uploadError = "文件上传失败，请重试。";
    }
}
$uploads = array_diff(scandir('uploads'), array('.', '..'));
$uploads = array_values($uploads);
$imagesPerPage = 10;
$totalImages = count($uploads);
$totalPages = ceil($totalImages / $imagesPerPage);
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $imagesPerPage;
$currentImages = array_slice($uploads, $start, $imagesPerPage);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>梦泽图床</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@iconify/iconify@2.0.1/dist/iconify.min.js"></script>
    <style>
        .blurred {
            filter: blur(4px);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .button-hover:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease-in-out;
        }

        .image-card:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease-in-out;
        }

        .loading-spinner {
            border-top-color: transparent;
            border-left-color: transparent;
            border-bottom-color: transparent;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4">

        <!-- 页面标题 -->
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8 fade-in">梦泽图床</h1>

        <!-- 上传表单 -->
        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="mb-8 bg-white p-6 rounded-lg shadow-lg max-w-xl mx-auto fade-in">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">上传图片</h2>
            <input type="file" name="image" id="image" class="block w-full p-3 bg-white border border-gray-300 rounded-lg mb-4 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <button type="submit" id="uploadBtn" class="w-full bg-blue-600 text-white p-3 rounded-lg shadow-md button-hover">
                上传图片
            </button>
        </form>

        <!-- 上传成功或失败的提示 -->
        <?php if (isset($uploadSuccess)) { ?>
            <div class="text-center text-green-600 mb-4 bg-green-100 p-4 rounded-lg shadow-md fade-in">
                文件上传成功！
            </div>
        <?php } elseif (isset($uploadError)) { ?>
            <div class="text-center text-red-600 mb-4 bg-red-100 p-4 rounded-lg shadow-md fade-in">
                <?= $uploadError ?>
            </div>
        <?php } ?>

        <!-- 上传进度条 -->
        <div id="progressContainer" class="mb-8 hidden">
            <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                <div id="progress-bar" class="bg-blue-600 h-4 rounded-full" style="width: 0%;"></div>
            </div>
            <p id="progress-text" class="text-center text-sm text-gray-500">上传进度: 0%</p>
        </div>

        <!-- 图片列表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            foreach ($currentImages as $file) {
                $filePath = 'uploads/' . $file;
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                $fileUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/img/' . $filePath;
                echo "
                    <div class='bg-white shadow-lg rounded-lg overflow-hidden transform image-card mb-4'>
                        <div class='relative'>
                            <img src='$filePath' alt='$fileName' class='w-full h-40 object-cover'>
                            <div class='absolute top-0 left-0 right-0 bottom-0 bg-black opacity-40'></div>
                            <div class='absolute top-0 left-0 p-4'>
                                <span class='text-white text-xl font-semibold'>$fileName</span>
                            </div>
                        </div>
                        <div class='p-4'>
                            <p class='text-sm text-gray-500'>$fileName</p>
                            <input type='text' value='$fileUrl' class='w-full p-2 text-sm bg-gray-100 border border-gray-300 rounded-lg mb-4' readonly>
                            <button onclick='copyLink(\"$fileUrl\")' class='w-full bg-green-500 text-white p-3 rounded-lg shadow-md button-hover'>
                                 复制链接
                            </button>
                        </div>
                    </div>
                ";
            }
            ?>
        </div>

        <!-- 分页控制 -->
        <div class="text-center">
            <?php if ($page > 1) { ?>
                <a href="?page=<?php echo $page - 1; ?>" class="text-blue-600">上一页</a>
            <?php } ?>
            <?php if ($page < $totalPages) { ?>
                <a href="?page=<?php echo $page + 1; ?>" class="text-blue-600 ml-4">下一页</a>
            <?php } ?>
        </div>

    </div>

    <script>
        function copyLink(url) {
            const input = document.createElement('input');
            document.body.appendChild(input);
            input.value = url;
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            alert('链接已复制到剪贴板');
        }
        document.getElementById('uploadForm').addEventListener('submit', function(event) {
            event.preventDefault();
            let formData = new FormData(this);
            let xhr = new XMLHttpRequest();
            document.getElementById('progressContainer').classList.remove('hidden');
            let uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.innerHTML = '<span class="iconify" data-icon="mdi:loading"></span> 上传中...';
            xhr.open('POST', '', true);
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    let percent = (e.loaded / e.total) * 100;
                    document.getElementById('progress-bar').style.width = percent + '%';
                    document.getElementById('progress-text').textContent = '上传进度: ' + Math.round(percent) + '%';
                }
            });
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('文件上传成功');
                    document.getElementById('progress-bar').style.width = '0%';
                    document.getElementById('progress-text').textContent = '上传进度: 0%';
                    uploadBtn.innerHTML = '上传图片';
                } else {
                    alert('上传失败，请重试');
                }
            };

            xhr.send(formData);
        });
    </script>
</body>
</html>