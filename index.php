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
        $renderer->registerTag('formblock', 'LiquidTagFormBlock');
        $renderer->registerTag('marker', 'LiquidTagMarker');
        $renderer->registerTag('plugin', 'LiquidTagPlugin');
        $renderer->registerTag('text', 'LiquidTagText');
        $renderer->registerTag('unless', 'LiquidTagUnless');
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
        return array_merge($this->load_assigns_file(), $_GET);
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

class LiquidBlockExtended extends LiquidBlock
{
    public function unknownTag($tag, $params, &$tokens)
    {
        echo $tag;
    }
}

class LiquidTagFormBlock extends LiquidBlockExtended {}
class LiquidTagMarker extends LiquidBlockExtended {}

class LiquidTagPlugin extends LiquidBlockExtended 
{
    public function __construct($markup, &$tokens, &$fileSystem)
    {
        $this->_markup = str_replace(' ', '', $markup);
    }

    public function render(&$context)
    {
        return '';
    }
}

class LiquidTagText extends LiquidTagPlugin
{
    public function render(&$context)
    {
        return $context->get($this->_markup);
    }
}

class LiquidTagUnless extends LiquidDecisionBlock
{
    private $_nodelistHolders = array();
    private $_blocks = array();

    public function __construct($markup, &$tokens, &$fileSystem)
    {
        $this->_nodelist = &$this->_nodelistHolders[count($this->_blocks)];

        array_push($this->_blocks, array(
            'unless', $markup, &$this->_nodelist
        ));

        parent::__construct($markup, $tokens, $fileSystem);
    }

    public function unknownTag($tag, $params, &$tokens)
    {
        if ($tag == 'else')
        {
            /* Update reference to nodelistHolder for this block */
            $this->_nodelist = &$this->_nodelistHolders[count($this->_blocks) + 1];
            $this->_nodelistHolders[count($this->_blocks) + 1] = array();

            array_push($this->_blocks, array(
                $tag, $params, &$this->_nodelist
            ));

        }
        else
        {
            parent::unknownTag($tag, $params, $tokens);
        }
    }

    public function render(&$context)
    {
        $context->push();

        $logicalRegex = new LiquidRegexp('/\s+(and|or)\s+/');
        $conditionalRegex = new LiquidRegexp('/(' . LIQUID_QUOTED_FRAGMENT . ')\s*([=!<>a-z_]+)?\s*(' . LIQUID_QUOTED_FRAGMENT . ')?/');

        $result = '';

        foreach($this->_blocks as $i => $block)
        {
            if ($block[0] == 'else')
            {
                $result = $this->renderAll($block[2], $context);

                break;
            }

            if ($block[0] == 'unless')
            {
                /* Extract logical operators */
                $logicalRegex->match($block[1]);

                $logicalOperators = $logicalRegex->matches;
                array_shift($logicalOperators);

                /* Extract individual conditions */
                $temp = $logicalRegex->split($block[1]);

                $conditions = array();

                foreach($temp as $condition)
                {
                    if ($conditionalRegex->match($condition))
                    {
                        $left = (isset($conditionalRegex->matches[1])) ? $conditionalRegex->matches[1] : null;
                        $operator = (isset($conditionalRegex->matches[2])) ? $conditionalRegex->matches[2] : null;
                        $right = (isset($conditionalRegex->matches[3])) ? $conditionalRegex->matches[3] : null;

                        array_push($conditions, array(
                                'left' => $left,
                                'operator' => $operator,
                                'right' => $right
                        ));
                    }
                    else
                    {
                        throw new LiquidException("Syntax Error in tag 'if' - Valid syntax: if [condition]");
                    }
                }

                if (count($logicalOperators))
                {
                    /* If statement contains and/or */
                    $display = true;

                    foreach($logicalOperators as $k => $logicalOperator)
                    {
                        if ($logicalOperator == 'and')
                        {
                            $display = !$this->_interpretCondition($conditions[$k]['left'], $conditions[$k]['right'], $conditions[$k]['operator'], $context) && $this->_interpretCondition($conditions[$k + 1]['left'], $conditions[$k + 1]['right'], $conditions[$k + 1]['operator'], $context);
                        }
                        else
                        {
                            $display = !$this->_interpretCondition($conditions[$k]['left'], $conditions[$k]['right'], $conditions[$k]['operator'], $context) || $this->_interpretCondition($conditions[$k + 1]['left'], $conditions[$k + 1]['right'], $conditions[$k + 1]['operator'], $context);
                        }
                    }

                }
                else
                {
                    /* If statement is a single condition */
                    $display = !$this->_interpretCondition($conditions[0]['left'], $conditions[0]['right'], $conditions[0]['operator'], $context);
                }

                if ($display)
                {
                    $result = $this->renderAll($block[2], $context);

                    break;
                }
            }
        }

        $context->pop();

        return $result;
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