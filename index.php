<?php
// require renderer
require_once 'php-liquid/Liquid.class.php';

// create our bootstrapper class
class Bootstrap
{
    // set up our vars
    private $file;
    private $path;
    private $template;

    public function __construct() 
    {
        // check requested route
        $this->check_route();
    }

    private function check_route()
    {
        // set template and file
        $this->file = $_GET['file'];
        $this->template = $_GET['template'];

        // check that a template has been requested
        if ($this->template) 
        {
            // render the template and file (otherwise render the default file)
            $this->render_template();
        } 
        else
        {
            // throw a wobbly
            throw new Exception('You need to specify a template!');
        }
    }

    private function render_template()
    {
        // set our paths
        $this->path = dirname(__FILE__) . sprintf('/template/%s/', $this->template);
        $template = $this->path . $this->file;

        // create renderer
        $renderer = new LiquidTemplate($this->path);

        // register shiz
        $renderer->registerTag('marker', 'LiquidTagMarker');
        $renderer->registerTag('plugin', 'LiquidTagPlugin');
        $renderer->registerFilter(new LiquidFilterAssetsURL());

        // parse the template
        $renderer->parse($this->get_file($template));

        // get the template assigns
        $template_assigns = $this->get_assigns();

        // render
        $base_assigns = array(
            'body' => $renderer->render($template_assigns),
            'stylesheets' => $template_assigns['stylesheets']
        );

        // add to wrapper and render
        $renderer->parse($this->get_file(dirname(__FILE__) . '/base.php'));

        echo $renderer->render($base_assigns);
    }

    private function get_file($file)
    {
        // get the file
        $file = file_get_contents($file);

        return $file;
    }

    private function get_assigns()
    {
        // GET vars are the default assigns
        // they're overwritten by the assigns.json, if it exists
        return array_merge($_GET, $this->load_assigns_file());
    }

    private function load_assigns_file()
    {
        // set our assigns file
        $file = $this->path . 'assigns.json';

        // check if the file
        if (file_exists($file)) 
        {
            // load it and load the json
            return json_decode(file_get_contents($file), true); // force to load json into an assoc array
        }
        else
        {
            // return an empty array
            return array();
        }
    }
}

class LiquidTagMarker extends LiquidBlock
{
    public function render(&$context)
    {
        return parent::render($context);
    }
}

class LiquidTagPlugin extends LiquidBlock
{
    public function __construct($markup, &$tokens, &$fileSystem)
    {
        // nothing
    }

    public function render(&$context)
    {
        return '';
    }
}

class LiquidFilterAssetsURL
{
    public function asset_url($file)
    {
        return '/' . $_GET['template'] . '/' . preg_replace('/^\//', '', $file);
    }
}

new Bootstrap();