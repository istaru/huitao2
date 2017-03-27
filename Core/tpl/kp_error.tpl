<!DOCTYPE html>
<html>
<head>
    <title>System Error</title>
    <link rel="stylesheet" href=<?php echo RES_SITE.'Core/tpl/public/css/default.css'?>>
    <script src=<?php echo RES_SITE.'Core/tpl/public/js/highlight.pack.js'?>></script>
    <script>hljs.initHighlightingOnLoad();</script>
    <style type="text/css">
        .top {
            border: solid 1px #ddd;
            margin:17px;
            padding:0 10px;
            overflow: hidden;
        }
        h1,ul,pre {
            margin: 0;
            padding:0;
        }
        .top .message h2 {
            color: #4288ce;
            font-size:20px;
            padding-bottom: 6px;
            border-bottom: 1px solid #eee;
        }
        ul {
            list-style: none;
        }
        code {
            font-size: 16px;
            line-height: 20px;
        }
        .error_info {
            font-size: 23px;
            font-weight: bold;
        }
        .line-error {
            background: #f8cbcb;
        }
        .k {
            padding-right:1%;
        }
    </style>
</head>
<body>
    <div class = "top">
        <div class="message">
            <div><h2><?php echo "[{$data['code']}]  HttpException in {$data['file']} line {$data['line']}"?></h2></div>
            <div class="error_info"><?php echo $data['message'] ?></div>
            <pre>
                <code>
                    <?php
                        if(!empty($data['content'])) {
                            foreach($data['content'] as $k => $v) {
                                if($k == $data['line'])
                                    echo '<div class="row line-error"><span class="k">'.$k.'</span><span class="val">'.$v.'</span></div>';
                                else
                                   echo '<div class="row "><span class="k">'.$k.'</span><span class="val">'.$v.'</span></div>';
                            }
                        }
                    ?>
                </code>
            </pre>
        </div>
    </div>
</body>

</html>