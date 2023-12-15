<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Options::class)]
class OptionsTest extends AbstractTestCase
{
    private Options $export;

    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        parent::setGlobalConfig();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        parent::loadDbiIntoContainerBuilder();

        $GLOBALS['server'] = 0;

        Current::$table = 'table';
        Current::$database = 'PMA';

        $this->export = new Options(
            new Relation($dbi),
            new TemplateModel($dbi),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Config::$instance = null;
    }

    public function testGetOptions(): void
    {
        $config = Config::getInstance();
        $config->settings['Export']['method'] = 'XML';
        $config->settings['SaveDir'] = '/tmp';
        $config->settings['ZipDump'] = false;
        $config->settings['GZipDump'] = false;

        $exportType = 'server';
        $db = 'PMA';
        $table = 'PMA_test';
        $numTablesStr = '10';
        $unlimNumRowsStr = 'unlim_num_rows_str';
        //$single_table = "single_table";
        DatabaseInterface::getInstance()->getCache()->cacheTableContent([$db, $table, 'ENGINE'], 'MERGE');

        $columnsInfo = [
            'test_column1' => ['COLUMN_NAME' => 'test_column1'],
            'test_column2' => ['COLUMN_NAME' => 'test_column2'],
        ];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->willReturn($columnsInfo);
        $dbi->expects($this->any())->method('getCompatibilities')
            ->willReturn([]);

        DatabaseInterface::$instance = $dbi;

        $exportList = Plugins::getExport($exportType, true);
        $dropdown = Plugins::getChoice($exportList, 'sql');
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['user'] = 'pma_user';
        $_POST['filename_template'] = 'user value for test';

        //Call the test function
        $actual = $this->export->getOptions($exportType, $db, $table, '', $numTablesStr, $unlimNumRowsStr, $exportList);

        $expected = [
            'export_type' => $exportType,
            'db' => $db,
            'table' => $table,
            'templates' => ['is_enabled' => '', 'templates' => [], 'selected' => null],
            'sql_query' => '',
            'hidden_inputs' => [
                'db' => $db,
                'table' => $table,
                'export_type' => $exportType,
                'export_method' => $config->settings['Export']['method'],
                'template_id' => '',
            ],
            'export_method' => $config->settings['Export']['method'],
            'plugins_choice' => $dropdown,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $config->settings['ExecTimeLimit'],
            'rows' => [],
            'has_save_dir' => true,
            'save_dir' => Util::userDir($config->settings['SaveDir']),
            'export_is_checked' => $config->settings['Export']['quick_export_onserver'],
            'export_overwrite_is_checked' => $config->settings['Export']['quick_export_onserver_overwrite'],
            'has_aliases' => false,
            'aliases' => [],
            'is_checked_lock_tables' => $config->settings['Export']['lock_tables'],
            'is_checked_asfile' => $config->settings['Export']['asfile'],
            'is_checked_as_separate_files' => $config->settings['Export']['as_separate_files'],
            'is_checked_export' => $config->settings['Export']['onserver'],
            'is_checked_export_overwrite' => $config->settings['Export']['onserver_overwrite'],
            'is_checked_remember_file_template' => $config->settings['Export']['remember_file_template'],
            'repopulate' => false,
            'lock_tables' => false,
            'is_encoding_supported' => true,
            'encodings' => Encoding::listEncodings(),
            'export_charset' => $config->settings['Export']['charset'],
            'export_asfile' => $config->settings['Export']['asfile'],
            'has_zip' => $config->settings['ZipDump'],
            'has_gzip' => $config->settings['GZipDump'],
            'selected_compression' => 'none',
            'filename_template' => 'user value for test',
        ];

        $this->assertEquals($expected, $actual);
    }
}
