<?php 
use \Jsnlib\Joomla\Template;

class Loader
{
    protected $HtmlDocument;
    protected $app;
    protected $input;
    protected $post;
    protected $get;
    protected $user;
    protected $option;
    protected $task;
    protected $assets;

    function __construct(Joomla\CMS\Document\HtmlDocument $HtmlDocument)
    {
        $this->HtmlDocument = $HtmlDocument;
        $this->app          = \JFactory::getApplication();
        $this->user         = \JFactory::getUser();
        $this->input        = $this->app->input;
        $this->post         = $this->input->post;
        $this->get          = $this->input->get;
        $this->task         = $this->input->get->get('task', 'default');
        $this->option       = $this->input->getCmd('option', 'com_content');
        $this->assets       = [];
    }

    /**
     * 將參數帶入並渲染
     * @param  callable $callback($properties) 可取得內部所有內部的屬性
     */
    public function render($callback = false): string
    {
        // 提取資源
        $assets = $this->getAssets();

        $result = $this->view("main.php",
        [
            'assets' => $assets,
            'doc' => $this->HtmlDocument
        ]);

        $properties = get_object_vars($this);

        $callback($properties);

        return $result;
    }

    // 匹配頁面與資源
    public function setAssets(array $assets = []):bool
    {
        $this->assets += $assets;
        $map = $this->getAssetsList();
        $this->checkFileExists($map);
        return true;
    }

    protected function eachFiles($option, $task, $files, callable $callback): void
    {
        foreach ($files as $box)
        {
            list($path, $type) = $box;

            if (empty($path)) throw new \Exception("資源格式指定錯誤，位於 {$this->option}.{$this->task}");

            $callback($type, $path, $task, $option);
        }
    }

    // 深入資源底層
    protected function eachAssetsDeep(callable $callback): void
    {
        // 取出每個 option 底下的所有 task
        if (is_array($this->assets)) foreach ($this->assets as $option => $asset)
        {
            // 取出每個 task 會用到的資源 
            foreach ($asset as $task => $files)
            {
                // 取出每個資源方塊
                $this->eachFiles($option, $task, $files, function ($type, $path, $task, $option) use ($callback)
                {
                    $callback($type, $path, $task, $option);
                });
            }
        }
    }

    // 取得二維陣列的資源列表
    protected function getAssetsList(): array
    {
        $box = [];

        $eachAssetsDeep = $this->eachAssetsDeep(function ($type, $path, $task, $option) use (&$box)
        {
            array_push($box, 
            [
                'type' => $type,
                'path' => $path,
                'task' => $task,
                'option' => $option,
            ]);
        });

        return $box;
    }

    // 檢查 CSS 是否存在
    public function checkFileExists(array $map): void
    {
        foreach ($map as $key => $filebox)
        {
            $isExist = file_exists(JPATH_ROOT . DIRECTORY_SEPARATOR . $filebox['path']);

            if ($isExist === true) continue;

            throw new \Exception("文件不存在：{$filebox['path']}");
        }
    }

    // 取得 view
    protected function view(string $view, array $param = []): string
    {
        $loader = new Twig_Loader_Filesystem(__DIR__ . '/views');
        $twig = new Twig_Environment($loader);
        return $twig->render($view, $param);
    }

    // 依照資源類型分類
    protected function diffAssetType(array $box, array $info): array
    {
        if ($info['type'] == "css") 
        {
            array_push($box['css'], $info['path']);
        }
        elseif ($info['type'] == "js")
        {
            array_push($box['js'], $info['path']);
        }
        return $box;
    }

    // 搜尋當前頁面所需要的資源
    protected function searchCurrAssets(array $box, array $assetsList, string $currOption, string $currTask): array
    {
        foreach ($assetsList as $key => $info)
        {
            // 不符合 option + task 則跳離
            if ($info['option'] !== $currOption) continue;

            if ($info['task'] !== $currTask) continue;

            // 依照資源類型分類
            $box = $this->diffAssetType($box, $info);
        }

        return $box;
    }

    // 搜尋痊癒資源
    protected function searchGlobalAssets(array $box, array $assetsList): array
    {
        foreach ($assetsList as $key => $info)
        {
            if ($info['option'] == "global" and $info['task'] == "global")
            {
                // 依照資源類型分類
                $box = $this->diffAssetType($box, $info);
            }
        }
        return $box;
    }

    // 取得當前頁面所需要的資源
    protected function getAssets(): array
    {
        $box        = ['css' => [], 'js' => []];
        
        // 當前資訊
        $currOption = $this->option;
        $currTask   = $this->task;

        $assetsList = $this->getAssetsList();

        // 提取符合當前頁面的資源
        $box = $this->searchCurrAssets($box, $assetsList, $currOption, $currTask);

        // 提取全域資源
        $box = $this->searchGlobalAssets($box, $assetsList);
        
        return $box;
    }

    

    // 取得模板底下的 CSS 相對路徑
    public function site(string $path): string
    {
        $fullpath = 'templates' . DIRECTORY_SEPARATOR . $this->HtmlDocument->template . DIRECTORY_SEPARATOR . $path;
        $replace = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullpath);
        return $replace;
    }
}