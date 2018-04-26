# joomla_template_loader
依照不同頁面，載入不同 CSS 與 JavaScript 資源，並將邏輯與視圖分離。

## 範例說明
- 模板位置 templates/mynewtemplate/，mynewtemplate 是我自訂的名稱
- 邏輯的部分寫在 index.php
- 視圖 view 的部分寫在 views/main.php

## 範例
### templates/mynewtemplate/index.php
指定資源後渲染視圖。
````php
<?php 
defined('_JEXEC') or die('Restricted access');
require_once JPATH_ROOT . '/vendor/autoload.php';

$loader = new \Jsnlib\Joomla\Template\Loader($this);

// 指定資源
$loader->setAssets(
[
    // 全域
    'global' => 
    [
        'global' => 
        [
            [$loader->site("css/template.css"), "css"],
        ]
    ],

    // 分頁
    'com_todolist' => 
    [
        // 當 task=form.index
        'form.index' => 
        [
            [$loader->site("css/template.css"), "css"],
        ],
        // 當 task=form.upload
        'form.upload' => 
        [
            [$loader->site("javascript/global.js"), "js"],
        ],
    ],

    // 首頁
    'com_content' => 
    [
        // 網址沒有 task 的時候
        'default' => 
        [
            [$loader->site("css/template.css"), "css"],
        ],
    ],
    
]);


echo $loader->render(function ($properties)
{
    // 可查看內部屬性
    print_r($properties['option']);

});

````

### templates/mynewtemplate/views/main.php
取出 CSS 與 JavaScript 的連結可以這麼寫，這個模板語言是 twig ，可參[考官方教學](https://twig.symfony.com/)
````html
{% for path in assets.css %}
    <link rel="stylesheet" type="text/css" href="{{ path }}">
{% endfor %}

{% for js in assets.js %}
    <script src="{{ js }}"></script>
{% endfor %}
````

例如
````html
<!DOCTYPE html>
<html xml:lang="{{doc.language}}" lang="{{doc.language}}" >
<head>
    <jdoc:include type="head" />
    {% for path in assets.css %}
        <link rel="stylesheet" type="text/css" href="{{ path }}">
    {% endfor %}
    {% for js in assets.js %}
        <script src="{{js}}"></script>
    {% endfor %}
</head>
<body>

    <header>
        <jdoc:include type="modules" name="header" style="header" />
    </header>

    <div class="container">
        <div class="top">
            <jdoc:include type="modules" name="top" style="banner" />
        </div>
        <div class="left">
            <jdoc:include type="modules" name="left" style="left" />
        </div>
        <div class="main">
            <jdoc:include type="component" />
        </div>
        <div class="right">
            <jdoc:include type="modules" name="right" style="right" />
        </div>
        <div style="clear: both"></div>
    </div>

    <footer>
        <jdoc:include type="modules" name="footer" style="footer" />
    </footer>
</body>
</html>
````


## API
### setAssets(array $assets = []):bool
指定資源的陣列，規則是
````
[
    '元件名稱' => 
    [
        '完整的 task 名稱' => 
        [
            [資源路徑, 文件類型]
        ]
    ]
]
````
例如
````
[
    'com_todolist' => 
    [
        'form.index' => 
        [
            [$loader->site("css/template.css"), "css"],
            [$loader->site("javascript/global.js"), "js"],
        ]
    ]
]
````
若要全域使用，元件與 task 請指定 global；若網址中沒有 task ，完整的 task 名稱請指定 default。

### site(string $path): string
自動取得模板的路徑。例如取得 ````$loader->site("css/template.css")```` 將會返回 ````templates/mynewtemplate/css/template.css````。

### render($callback = false): string
將參數帶入並渲染位於 views/main.php 的視圖。
````php
echo $loader->render(function ($properties)
{
    // 可查看內部屬性
    print_r($properties['option']);
});
````
