<?php
    // $Id: sample_test.php 8575 2007-11-13 14:41:00Z mlunzena $
    
    class SampleTestForArrayReporter extends UnitTestCase {
        
        function testTrueIsTrue() {
            $this->assertTrue(true);
        }

        function testFalseIsTrue() {
            $this->assertFalse(true);
        }

    }
?>