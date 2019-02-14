<?php

namespace mozzler\base\tests;

use mozzler\base\cron\CronEntry;
use Yii;
use mozzler\base\components\Tools;

class CronManagerTest extends \Codeception\Test\Unit
{

    public function testShouldRunCronAtTime()
    {

        // Test the minutes, hours, dayMonth, months, dayWeek, timezone, and active
        $timezoneAdelaide = new \DateTimeZone('Australia/Adelaide');

        $timestampVday = 1550108091; // Wed, 13 Feb 2019 15:04:00 +0000 = 1550108091 (or Thu, 14 Feb 2019 12:04:51 +1030 Adelaide time)
        $timestampNYE = $this->getUtcTimestamp('2018-12-31 23:59:59', $timezoneAdelaide); // Mon, 31 Dec 2018 23:59:59 +0000 = 1546262999 1546225199 1546300799
        $timestampNYD = $this->getUtcTimestamp('2000-01-01', $timezoneAdelaide); // Sat, 01 Jan 2000 00:00:00 +0000 = 946647000
        $timestampAprilFoolsStart = $this->getUtcTimestamp('2019-05-01', $timezoneAdelaide); // Wed, 01 May 2019 00:00:00 ++0000 = 1556634600
        $timestampAprilFoolsEnd = $this->getUtcTimestamp('2019-05-01 12:00', $timezoneAdelaide); // Wed, 01 May 2019 12:00:00 +0000 = 1556677800
        $timestampBday = $this->getUtcTimestamp('1984-10-27 04:30', $timezoneAdelaide); // Sat, 27 Oct 1984 04:30:00 +0000 = 467699400

        $cronEntry = \Yii::createObject(
            ['class' => CronEntry::class,
                'scriptClass' => 'mozzler\base\scripts\ScriptBase',
                'config' => [],
                'minutes' => '*',
                'hours' => '*',
                'dayMonth' => '*',
                'months' => '*',
                'dayWeek' => '*',
                'timezone' => 'Australia/Adelaide',
                'active' => true,
                'timeoutSeconds' => 120,
            ]);

        // ----- Running ALL the time
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampVday), 'All the Time');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYE), 'All the Time');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'All the Time');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'All the Time');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'All the Time');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampBday), 'All the Time');


        // -- Active = false
        $cronEntry->active = false;
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampVday), 'Active = False');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampNYE), 'Active = False');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampNYD), 'Active = False');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'Active = False');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'Active = False');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampBday), 'Active = False');
        $cronEntry->active = true; // Restore back

        // -- Check minutes
        $cronEntry->minutes = '0,1,3,5,7,9,10,15,20,25,40,50,55';
        $this->assertfalse($cronEntry->shouldRunCronAtTime($timestampVday), 'timestampVday Check Minute');
        $this->assertfalse($cronEntry->shouldRunCronAtTime($timestampNYE), 'timestampNYE Check Minute');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'timestampNYD Check Minute');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'timestampAprilFoolsStart Check Minute');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'timestampAprilFoolsEnd Check Minute');
        $this->assertfalse($cronEntry->shouldRunCronAtTime($timestampBday), 'timestampBday Check Minute');
        $cronEntry->minutes = '*'; // Restore


        // -- Check Hours
        $cronEntry->hours = '0,1,3,5,7,9,10,15,20';
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampVday), 'timestampVday Check Hours');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampNYE), 'timestampNYE Check Hours');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'timestampNYD Check Hours');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'timestampAprilFoolsStart Check Hours');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'timestampAprilFoolsEnd Check Hours');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampBday), 'timestampBday Check Hours');
        $cronEntry->hours = '*'; // Restore

        // -- Check dayMonth
        $cronEntry->dayMonth = '1,3,7,9,10,15,20,28,31';
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampVday), 'timestampVday check dayMonth');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYE), 'timestampNYE check dayMonth');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'timestampNYD check dayMonth');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'timestampAprilFoolsStart check dayMonth');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'timestampAprilFoolsEnd check dayMonth');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampBday), 'timestampBday check dayMonth');
        $cronEntry->dayMonth = '*'; // Restore


        // -- Check months
        $cronEntry->months = '1,3,7,9,10,12';
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampVday), 'timestampVday check months');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYE), 'timestampNYE check months');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'timestampNYD check months');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'timestampAprilFoolsStart check months');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'timestampAprilFoolsEnd check months');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampBday), 'timestampBday check months');
        $cronEntry->months = '*'; // Restore


        // -- Check dayWeek
        $cronEntry->dayWeek = '0,3,6'; // Sunday, Wednesday, Saturday
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampVday), 'timestampVday check dayWeek');
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampNYE), 'timestampNYE check dayWeek');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'timestampNYD check dayWeek');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'timestampAprilFoolsStart check dayWeek');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'timestampAprilFoolsEnd check dayWeek');
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampBday), 'timestampBday check dayWeek');
        $cronEntry->dayWeek = '*'; // Restore


        // -- Check timezone
        $cronEntry->timezone = 'America/Los_Angeles';
        $cronEntry->hours = '0,5,12,17';
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampVday), 'timestampVday check timezone'); // Wed, 13 Feb 2019 17:34:00 -0800
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYE), 'timestampNYE check timezone'); // Mon, 31 Dec 2018 05:29:00 -0800
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampNYD), 'timestampNYD check timezone'); // Fri, 31 Dec 1999 05:30:00 -0800
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsStart), 'timestampAprilFoolsStart check timezone'); // Tue, 30 Apr 2019 07:30:00 -0700
        $this->assertFalse($cronEntry->shouldRunCronAtTime($timestampAprilFoolsEnd), 'timestampAprilFoolsEnd check timezone'); // Tue, 30 Apr 2019 19:30:00 -0700
        $this->assertTrue($cronEntry->shouldRunCronAtTime($timestampBday), 'timestampBday check timezone'); // Fri, 26 Oct 1984 12:00:00 -0700
        $cronEntry->timezone = '*'; // Restore
        $cronEntry->hours = '*'; // Restore

        // Done!
    }


    function getUtcTimestamp($timeString = 'now', $timeZone = 'Australia/Adelaide')
    {
        if (is_string($timeZone)) {
            $timeZone = new \DateTimeZone($timeZone);
        }
        $dateTime = new \DateTime($timeString, $timeZone);

        return $dateTime->getTimestamp();
    }

}
