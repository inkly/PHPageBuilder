<?php

namespace PHPageBuilder\Modules\GrapesJS\Block;

use PHPageBuilder\Modules\GrapesJS\PageRenderer;
use PHPageBuilder\ThemeBlock;
use Exception;

/**
 * Class BlockAdapter
 *
 * Class for adapting a ThemeBlock into a JSON object understood by the GrapesJS page builder.
 *
 * @package PHPageBuilder\GrapesJS
 */
class BlockAdapter
{
    /**
     * @var PageRenderer $pageRenderer
     */
    protected $pageRenderer;

    /**
     * @var ThemeBlock $block
     */
    protected $block;

    /**
     * BlockAdapter constructor.
     *
     * @param PageRenderer $pageRenderer
     * @param ThemeBlock $block
     */
    public function __construct(PageRenderer $pageRenderer, ThemeBlock $block)
    {
        $this->pageRenderer = $pageRenderer;
        $this->block = $block;
    }

    /**
     * Return the slug identifying this type of theme block.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->block->getSlug();
    }

    /**
     * Return the visible title of this block.
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->block->get('title')) {
            return $this->block->get('title');
        }
        return str_replace('-', ' ', ucfirst($this->getSlug()));
    }

    /**
     * Return the category this block belongs to.
     *
     * @return string|null
     */
    public function getCategory()
    {
        if ($this->block->get('category')) {
            return $this->block->get('category');
        }
        return phpb_trans('pagebuilder.default-category');
    }

    /**
     * Return an array representation of the theme block, for adding as a block to GrapesJS.
     *
     * @return array
     * @throws Exception
     */
    public function getBlockManagerArray()
    {
        $blockShortcode = '[block slug="' . e($this->block->getSlug()) . '"]';
        $content = $this->pageRenderer->parseShortcode($blockShortcode);

        $img = '';
        if (file_exists($this->block->getThumbPath())) {
            $img = '<div class="block-thumb"><img src="' . phpb_full_url($this->block->getThumbUrl()) . '"></div>';
        }

        $data = [
            'label' => $img . $this->getTitle(),
            'category' => $this->getCategory(),
            'content' => $content
        ];

        if (! $img) {
            $iconClass = 'fa fa-bars';
            if ($this->block->get('icon')) {
                $iconClass = $this->block->get('icon');
            }
            $data['attributes'] = ['class' => $iconClass];
        }

        return $data;
    }

    /**
     * Return the array of settings of the theme block, for populating the settings tab in the GrapesJS sidebar.
     *
     * @return array
     */
    public function getBlockSettingsArray()
    {
        $configSettings = $this->block->get('settings');
        if ($this->block->isHtmlBlock() || ! is_array($configSettings)) {
            return [];
        }

        $settings = [];
        foreach ($configSettings as $name => $setting) {
            if (! isset($setting['label'])) {
                continue;
            }

            $settings[] = [
                'type' => $setting['type'] ?? 'text',
                'name' => $name,
                'label' => $setting['label'],
                'default-value' => $setting['value'] ?? ''
            ];
        }

        return $settings;
    }
}
