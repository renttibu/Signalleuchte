<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class SignalleuchteValidationTest extends TestCaseSymconValidation
{
    public function testValidateSignalleuchte(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateSignalleuchteModule(): void
    {
        $this->validateModule(__DIR__ . '/../Signalleuchte');
    }
}