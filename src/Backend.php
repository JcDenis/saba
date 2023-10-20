<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Label,
    Para
};
use Dotclear\Interface\Core\BlogSettingsInterface;

/**
 * @brief       saba backend class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            // add blog preferences form
            'adminBlogPreferencesFormV2' => function (BlogSettingsInterface $blog_settings): void {
                echo
                '<div class="fieldset">' .
                '<h4 id="saba_params">' . __('Search Across Blog Archive') . '</h4>' .

                // saba_active
                (new Para())->items([
                    (new Checkbox('saba_active', (bool) $blog_settings->get(My::id())->get('active')))->value(1),
                    (new Label(__('Enable advanced search on this blog'), Label::OUTSIDE_LABEL_AFTER))->for('saba_active')->class('classic'),
                ])->render() .
                // saba_error
                (new Para())->items([
                    (new Checkbox('saba_error', (bool) $blog_settings->get(My::id())->get('error')))->value(1),
                    (new Label(__('Enable suggestion for page 404'), Label::OUTSIDE_LABEL_AFTER))->for('saba_error')->class('classic'),
                ])->render() .

                '<p class="form-note">' .
                __('This suggests visitors some posts on page 404.') .
                '</p>' .
                '</div>';
            },
            // save blog preference form
            'adminBeforeBlogSettingsUpdate' => function (BlogSettingsInterface $blog_settings): void {
                $blog_settings->get(My::id())->put('active', !empty($_POST['saba_active']));
                $blog_settings->get(My::id())->put('error', !empty($_POST['saba_error']));
            },
            // init widget
            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
