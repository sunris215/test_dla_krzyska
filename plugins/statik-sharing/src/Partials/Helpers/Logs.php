<?php

declare(strict_types=1);

\defined('ABSPATH') || exit;

use Statik\Sharing\Dashboard\Page\HelpersPage;
use Statik\Sharing\Logger;

/**
 * @var HelpersPage $this
 */
$logsFiles = $this->getLogsDates();

$date = \filter_input(\INPUT_GET, 'date', \FILTER_SANITIZE_STRING);
$date = empty($date) ? \array_key_first($logsFiles) : $date;

?>

<form method="GET">
    <input type="hidden" name="page" value="<?= $_GET['page'] ?? ''; ?>">

    <label for="date">
        <?= \__('Date', 'statik'); ?>:
        <select name="date" id="date">
            <?php foreach ($logsFiles as $logDate => $log) { ?>
                <option value="<?= $logDate; ?>" <?= \selected($logDate, $_GET['date'] ?? ''); ?>>
                    <?= $logDate; ?>
                </option>
            <?php } ?>
        </select>
    </label>

    <label for="level">
        <?= \__('Level', 'statik'); ?>
        <select name="level" id="level">
            <option value="">All</option>
            <?php foreach (Logger::getLevels() as $name => $level) { ?>
                <option value="<?= $level; ?>" <?= \selected($level, $_GET['level'] ?? ''); ?>>
                    <?= \ucfirst(\strtolower($name)); ?>
                </option>
            <?php } ?>
        </select>
    </label>

    <label for="search">
        <?= \__('Search', 'statik'); ?>:
        <input type="text" name="search" id="search" value="<?= $_GET['search'] ?? ''; ?>">
    </label>

    <label for="time_from">
        <?= \__('Time from', 'statik'); ?>:
        <input type="time" name="time_from" id="time_from" value="<?= $_GET['time_from'] ?? ''; ?>">
    </label>

    <label for="time_to">
        <?= \__('Time to', 'statik'); ?>:
        <input type="time" name="time_to" id="time_to" value="<?= $_GET['time_to'] ?? ''; ?>">
    </label>

    <input type="submit" value="<?= \__('Filter', 'statik'); ?>" class="button button-primary">
</form>

<div class="table-wrapper" id="statik_logs_page">
    <table class="widefat logs-table">
        <thead>
        <tr>
            <th class="x-small"><?= \__('No.', 'statik'); ?></th>
            <th class="large"><?= \__('Time', 'statik'); ?></th>
            <th class="small"><?= \__('Type', 'statik'); ?></th>
            <th class="small"><?= \__('Action', 'statik'); ?></th>
            <th class="medium"><?= \__('Source Blog', 'statik'); ?></th>
            <th class="medium"><?= \__('Source Post', 'statik'); ?></th>
            <th class="medium"><?= \__('Destination Blog', 'statik'); ?></th>
            <th class="medium"><?= \__('Destination Post', 'statik'); ?></th>
            <th class="medium"><?= \__('User', 'statik'); ?></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th class="x-small"><?= \__('No.', 'statik'); ?></th>
            <th class="large"><?= \__('Time', 'statik'); ?></th>
            <th class="small"><?= \__('Type', 'statik'); ?></th>
            <th class="small"><?= \__('Action', 'statik'); ?></th>
            <th class="medium"><?= \__('Source Blog', 'statik'); ?></th>
            <th class="medium"><?= \__('Source Post', 'statik'); ?></th>
            <th class="medium"><?= \__('Destination Blog', 'statik'); ?></th>
            <th class="medium"><?= \__('Destination Post', 'statik'); ?></th>
            <th class="medium"><?= \__('User', 'statik'); ?></th>
        </tr>
        </tfoot>
        <tbody>
        <?= $this->getCurrentLog($date); ?>
        </tbody>
    </table>
</div>