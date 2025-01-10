<?php
// 文件上传处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $targetDir = "uploads/";

    // 获取文件信息
    $originalFileName = $_FILES["file"]["name"];
    $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

    // 生成新的文件名：年月日_随机五位字母.后缀
    $newFileName = date("Y-m-d") . '_' . substr(md5(rand()), 0, 5) . '.' . $fileExtension;
    $targetFile = $targetDir . $newFileName;

    // 移动文件到目标目录
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        $uploadSuccess = true;
    } else {
        $uploadError = "文件上传失败，请重试。";
    }
}

// 获取所有上传的文件，按文件名排序（以便分页）
$uploads = array_diff(scandir('uploads'), array('.', '..'));
$uploads = array_values($uploads);

// 每页显示 10 个文件
$filesPerPage = 10;
$totalFiles = count($uploads);
$totalPages = ceil($totalFiles / $filesPerPage);

// 获取当前页
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $filesPerPage;
$currentFiles = array_slice($uploads, $start, $filesPerPage);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网盘</title>
    <link href="https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script src="https://unpkg.com/@iconify/iconify@2.0.1/dist/iconify.min.js"></script>
    <style>
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

        /* 修复进度条样式 */
        .progress-container {
            display: none;
            margin-top: 10px;
        }

        .progress-bar {
            height: 5px;
            background-color: #3498db;
            width: 0%;
        }

        /* 修复响应式布局 */
        .upload-container {
            max-width: 800px;
        }

        .file-card {
            transition: transform 0.3s ease-in-out;
        }

        .file-card:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4">

        <!-- 页面标题 -->
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8 fade-in">网盘</h1>

        <!-- 上传表单 -->
        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="mb-8 bg-white p-6 rounded-lg shadow-lg upload-container mx-auto fade-in">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">上传文件</h2>
            <input type="file" name="file" id="file" class="block w-full p-3 bg-white border border-gray-300 rounded-lg mb-4 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <button type="submit" id="uploadBtn" class="w-full bg-blue-600 text-white p-3 rounded-lg shadow-md button-hover">
                上传文件
            </button>

            <!-- 进度条 -->
            <div id="progressContainer" class="progress-container">
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div id="progress-bar" class="progress-bar"></div>
                </div>
                <p id="progress-text" class="text-center text-sm text-gray-500">上传进度: 0%</p>
            </div>
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

        <!-- 文件列表 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            <?php
            // 遍历当前页的文件
            foreach ($currentFiles as $file) {
                $filePath = 'uploads/' . $file;
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                $fileUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $file;
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                echo "
                    <div class='bg-white shadow-lg rounded-lg overflow-hidden transform transition duration-300 ease-in-out hover:scale-105'>
                        <div class='relative'>
                            <div class='h-40 bg-gray-100 flex items-center justify-center'>
                                <span class='text-xl font-semibold text-gray-500'>$fileExtension</span>
                            </div>
                            <div class='absolute top-0 left-0 right-0 bottom-0 bg-black opacity-40'></div>
                            <div class='absolute top-0 left-0 p-4'>
                                <span class='text-white text-xl font-semibold'>$fileName</span>
                            </div>
                        </div>
                        <div class='p-4'>
                            <p class='text-sm text-gray-500 mb-2'>$fileExtension 文件</p>
                            <input type='text' value='$fileUrl' class='w-full p-2 text-sm bg-gray-100 border border-gray-300 rounded-lg mb-4' readonly>
                            <div class='flex gap-4'>
                                <button onclick='copyLink(\"$fileUrl\")' class='flex-1 bg-green-500 text-white p-3 rounded-lg shadow-md button-hover'>
                                     复制链接
                                </button>
                                <a href='$fileUrl' download class='flex-1 bg-blue-500 text-white p-3 rounded-lg shadow-md button-hover'>
                                     下载
                                </a>
                            </div>
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

    <!-- 复制链接的脚本 -->
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

        // 实现文件上传和进度条
        document.getElementById('uploadForm').addEventListener('submit', function(event) {
            event.preventDefault(); // 防止表单默认提交

            let formData = new FormData(this);
            let xhr = new XMLHttpRequest();

            document.getElementById('progressContainer').style.display = 'block'; // 显示进度条
            let uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.innerHTML = '<span class="iconify" data-icon="mdi:loading"></span> 上传中...'; // 显示加载动画

            xhr.open('POST', '', true);

            // 更新进度条
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    let percent = (e.loaded / e.total) * 100;
                    document.getElementById('progress-bar').style.width = percent + '%';
                    document.getElementById('progress-text').textContent = '上传进度: ' + Math.round(percent) + '%';
                }
            });

            // 上传完成后的回调
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // 这里可以处理服务器返回的数据
                    alert('文件上传成功');
                    document.getElementById('progress-bar').style.width = '0%';
                    document.getElementById('progress-text').textContent = '上传进度: 0%';
                    uploadBtn.innerHTML = '上传文件'; // 恢复按钮文本
                } else {
                    alert('上传失败，请重试');
                }
            };

            xhr.send(formData);
        });
    </script>
</body>
</html>            filter: blur(4px);
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
                $fileUrl = 'https://' . $_SERVER['HTTP_HOST'] . $filePath;
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
