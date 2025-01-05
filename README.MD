# Simple-Image-Uploader
**需要使用直接克隆本仓库或点击“Code->Download ZIP”进行下载**

一个简易的使用PHP编写的图床工具，界面还是挺好看的，上传成功之后需要手动刷新一下。图片列表目前是分页显示，没有做上滑分页。

目前js，css都是在同一个文件里面，界面使用TailWindCSS编写，还是蛮好看的。如果在子目录使用的话，记得在第111行如下代码加上子目录，就像这样：
```PHP
$fileUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/子目录/ .$filePath;
```
有能力的可以自己二改。

# LICENSE
遵循**MIT**开源协议开源
