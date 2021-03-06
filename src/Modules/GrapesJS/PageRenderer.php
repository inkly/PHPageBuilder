<?php

namespace PHPageBuilder\Modules\GrapesJS;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Modules\GrapesJS\Block\BlockRenderer;
use PHPageBuilder\ThemeBlock;
use Exception;

class PageRenderer
{
    /**
     * @var ThemeContract $theme
     */
    protected $theme;

    /**
     * @var PageContract $page
     */
    protected $page;

    /**
     * @var array $pageData
     */
    protected $pageData;

    /**
     * @var array $pageBlocksData
     */
    protected $pageBlocksData;

    /**
     * @var ShortcodeParser $shortcodeParser
     */
    protected $shortcodeParser;

    /**
     * @var bool $forPageBuilder
     */
    protected $forPageBuilder;

    /**
     * PageRenderer constructor.
     *
     * @param ThemeContract $theme
     * @param PageContract $page
     * @param bool $forPageBuilder
     */
    public function __construct(ThemeContract $theme, PageContract $page, $forPageBuilder = false)
    {
        $this->theme = $theme;
        $this->page = $page;
        $this->pageData = $page->getData();
        $this->pageBlocksData = $this->getPageBlocksData();
        $this->shortcodeParser = new ShortcodeParser($this);
        $this->forPageBuilder = $forPageBuilder;
    }

    /**
     * Return the absolute path to the layout view of this page.
     *
     * @return string
     */
    public function getPageLayoutPath()
    {
        $layout = basename($this->page->getLayout());
        return $this->theme->getFolder() . '/layouts/' . $layout . '/view.php';
    }

    /**
     * Return an array with for each block of this page the stored html & settings data.
     *
     * @return array
     */
    public function getPageBlocksData()
    {
        return $this->pageData['blocks'] ?? [];
    }

    /**
     * Return the rendered version of the page.
     *
     * @return string
     * @throws Exception
     */
    public function render()
    {
        // init variables that should be accessible in the view
        $renderer = $this;
        $page = $this->page;
        if ($this->forPageBuilder) {
            $body = '<div phpb-content-container="true"></div>';
        } else {
            $body = $this->renderBody();
        }

        ob_start();
        require $this->getPageLayoutPath();
        $pageHtml = ob_get_contents();
        ob_end_clean();

        // parse any shortcodes present in the page layout
        $pageHtml = $this->shortcodeParser->doShortcodes($pageHtml);

        return $pageHtml;
    }

    /**
     * Return the page body for display on the website.
     * The body contains all blocks which are put into the selected layout.
     *
     * @return string
     * @throws Exception
     */
    public function renderBody()
    {
        $html = '';

        $data = $this->pageData;
        if (isset($data['html'])) {
            $html .= $this->shortcodeParser->doShortcodes($data['html']);
        }
        // include any style changes made via the page builder
        if (isset($data['css'])) {
            $html .= '<style>' . $data['css'] . '</style>';
        }

        return $html;
    }

    /**
     * Include a rendered theme block with the given slug, data instance id and data context.
     * This method is called on parsing shortcodes.
     *
     * @param $slug
     * @param null $id                  the id with which data for this block is stored
     * @param null $parentBlockId
     * @return string
     */
    public function renderBlock($slug, $id = null, $parentBlockId = null)
    {
        $themeBlock = new ThemeBlock($this->theme, $slug);

        $pageBlocksData = $this->pageBlocksData;
        $blockData = null;
        // get data for this block stored in the context of the parent block
        if (! is_null($parentBlockId) && isset($pageBlocksData[$parentBlockId]) && isset($pageBlocksData[$parentBlockId][$id])) {
            $blockData = $pageBlocksData[$parentBlockId][$id];
        } elseif (isset($pageBlocksData[$id])) {
            // if no data is stored in context of the parent block, get data stored for this block's id
            $blockData = $pageBlocksData[$id];
        }

        $blockRenderer = new BlockRenderer($this->theme, $this->page, $this->forPageBuilder);
        $html = $blockRenderer->render($themeBlock, $blockData);

        if ($this->forPageBuilder) {
            $id = $id ?? $slug;
            $html = '<phpb-block block-slug="' . e($slug) . '" block-id="' . e($id) . '" is-html="' . ($themeBlock->isHtmlBlock() ? 'true' : 'false') . '">'
                . $html
                . '</phpb-block>';
        }

        return $html;
    }

    /**
     * Parse the given shortcode to html.
     *
     * @param string $shortcode
     * @param array $data           the data for each block to be used wile parsing the shortcode
     * @return string
     * @throws Exception
     */
    public function parseShortcode(string $shortcode, $data = [])
    {
        $originalData = $this->pageBlocksData;

        // parse the shortcode with the data array passed as an argument to this method
        $this->pageBlocksData = $data;
        $html = $this->shortcodeParser->doShortcodes($shortcode);

        $this->pageBlocksData = $originalData;
        return $html;
    }

    /**
     * Return this page's dynamic blocks to be loaded into the page edited inside GrapesJS.
     *
     * @return array
     * @throws Exception
     */
    public function getDynamicBlocks()
    {
        // trigger renderBody to ensure the shortcode parser has rendered all dynamic blocks
        $this->renderBody();
        // return the rendered html and settings for each dynamic block
        return $this->shortcodeParser->getRenderedBlocks();
    }
}
