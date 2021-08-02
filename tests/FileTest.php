<?php

namespace mozzler\base\tests;

use mozzler\base\cron\CronEntry;
use mozzler\base\models\File;
use Yii;
use mozzler\base\components\Tools;

class FileTest extends \Codeception\Test\Unit
{

    /**
     * @throws \yii\base\InvalidConfigException
     *
     * Test the filterFilename function
     */
    public function testFilenameFiltering()
    {


        $filenames = [
            'plainExample' => 'plainExample',
            'plainExample.PDF' => 'plainExample.pdf',
            'plainExample.pdf' => 'plainExample.pdf', // Ensure the processed version doesn't change
            'worryingExample-#(&*$TY(P#$*&YG()@*$Y@*G#-.asfjlair%G#%$G#$(*Y@)#$*Y*@#$' => 'worryingExample-#(&-$TY(P#$-&YG()@-$Y@-G#.asfjlair%g#%$g#$(-y@)#$-y-@#$',
            'worryingExample that\'s been_partly_processed-#(&-$TY(P#$-&YG()@-$Y@-G#.asfjlair%g#%$g#$(-y@)#$-y-@#$' => 'worryingExample-that\'s-been_partly_processed-#(&-$TY(P#$-&YG()@-$Y@-G#.asfjlair%g#%$g#$(-y@)#$-y-@#$',
            'dangerous Example \x00 \x02 \x1F \x7F<>:/\\\|?*.eXaMpLe\n' => 'dangerous-Example-x00-x02-x1F-x7F.example-n', // NB: This turns into a binary string if you double quote it and try to interpret the characters as proper control characters
            '/full/linux/folder/path/Example.jpeg2000' => 'full-linux-folder-path-Example.jpeg2000', // Don't use on folders
            'c:\full\windows\folder\path\Example.jpeg2000' => 'c-full-windows-folder-path-Example.jpeg2000', // Nor on Windows folders
        ];
        $file = \Yii::$app->t::createModel(File::class);

        $index = 0;
        foreach ($filenames as $filenameInput => $filenameExpectedOutput) {
            $filenameActualOutput = $file->filterFilename($filenameInput);
            $this->assertEquals($filenameExpectedOutput, $filenameActualOutput, "$index. Input: $filenameInput");
            $index++;
        }

    }
}
