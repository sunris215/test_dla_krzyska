<?php

declare(strict_types=1);

use Statik\Sharing\Helper\CustomPostType;

\defined('ABSPATH') || exit;

?>
<form method="POST">
    <?php \wp_nonce_field('statik_pages_exporter_nonce'); ?>

    <div class="statik-settings-grid statik-generator">
        <div class="statik-grid-row">
            <div class="statik-grid-col">
                <label for="statik_sharing_export[cpt]">Custom post type <sup>*</sup></label>
            </div>
            <div class="statik-grid-col input">
                <div>
                    <select name="statik_sharing_export[cpt]" class="regular-text" required="required">
                        <?php foreach (CustomPostType::getAllCPTs() as $cpt => $name) { ?>
                            <option value="<?= $cpt; ?>"><?= $name; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="statik-grid-col">
                <label for="statik_sharing_export[separator]">Separator <sup>*</sup></label>
            </div>
            <div class="statik-grid-col input">
                <div>
                    <select name="statik_sharing_export[separator]" class="regular-text" required="required">
                        <option value="semicolon" selected="selected">Semicolon</option>
                        <option value="coma">Coma</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <input type="submit" name="submit" id="submit" class="button button-primary" value="Export CSV file">
</form>