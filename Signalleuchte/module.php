<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

/*
 * @module      Signalleuchte
 *
 * @prefix      SIGL
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020
 * @license    	CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @see         https://github.com/ubittner/Signalleuchte
 *
 * @guids       Library
 * 	            {BFB49220-5188-61CA-2C21-85A457F8D77B}
 *
 *              Signalleuchte
 *              {CF6B75A5-C573-7030-0D75-2F50A8A42B73}
 */

declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/autoload.php';

class Signalleuchte extends IPSModule
{
    // Helper
    use SIGL_backupRestore;
    use SIGL_signalLamp;

    // Constants
    private const DELAY_MILLISECONDS = 250;
    private const SIGNALLEUCHTE_LIBRARY_GUID = '{BFB49220-5188-61CA-2C21-85A457F8D77B}';
    private const SIGNALLEUCHTE_MODULE_GUID = '{CF6B75A5-C573-7030-0D75-2F50A8A42B73}';

    public function Create()
    {
        // Never delete this line!
        parent::Create();
        $this->RegisterProperties();
        $this->CreateProfiles();
        $this->RegisterVariables();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();
        $this->DeleteProfiles();
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        // Never delete this line!
        parent::ApplyChanges();
        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->SetOptions();
        $this->CheckMaintenanceMode();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderID: ' . $SenderID . ', Message: ' . $Message . ' Data: ' . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $moduleInfo = [];
        $library = IPS_GetLibrary(self::SIGNALLEUCHTE_LIBRARY_GUID);
        $module = IPS_GetModule(self::SIGNALLEUCHTE_MODULE_GUID);
        $moduleInfo['name'] = $module['ModuleName'];
        $moduleInfo['version'] = $library['Version'] . '-' . $library['Build'];
        $moduleInfo['date'] = date('d.m.Y', $library['Date']);
        $moduleInfo['time'] = date('H:i', $library['Date']);
        $moduleInfo['developer'] = $library['Author'];
        $formData['elements'][0]['items'][2]['caption'] = "Instanz ID:\t\t" . $this->InstanceID;
        $formData['elements'][0]['items'][3]['caption'] = "Modul:\t\t\t" . $moduleInfo['name'];
        $formData['elements'][0]['items'][4]['caption'] = "Version:\t\t\t" . $moduleInfo['version'];
        $formData['elements'][0]['items'][5]['caption'] = "Datum:\t\t\t" . $moduleInfo['date'];
        $formData['elements'][0]['items'][6]['caption'] = "Uhrzeit:\t\t\t" . $moduleInfo['time'];
        $formData['elements'][0]['items'][7]['caption'] = "Entwickler:\t\t" . $moduleInfo['developer'];
        $formData['elements'][0]['items'][8]['caption'] = "Präfix:\t\t\tSIGL";
        return json_encode($formData);
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'SystemStateColor':
                $this->SetValue('SystemStateColor', $Value);
                $brightness = (int) $this->GetValue('SystemStateBrightness');
                $this->SetSystemStateSignalLamp(3, $Value, $brightness, 0);
                break;

            case 'SystemStateBrightness':
                $this->SetValue('SystemStateBrightness', $Value);
                $color = (int) $this->GetValue('SystemStateColor');
                $this->SetSystemStateSignalLamp(3, $color, $Value, 0);
                break;

            case 'DoorWindowStateColor':
                $this->SetValue('DoorWindowStateColor', $Value);
                $brightness = (int) $this->GetValue('DoorWindowStateBrightness');
                $this->SetDoorWindowStateSignalLamp(2, $Value, $brightness, 0, 0);
                break;

            case 'DoorWindowStateBrightness':
                $this->SetValue('DoorWindowStateBrightness', $Value);
                $color = (int) $this->GetValue('DoorWindowStateColor');
                $this->SetDoorWindowStateSignalLamp(2, $color, $Value, 0, 0);
                break;

            case 'AlarmStateColor':
                $this->SetValue('AlarmStateColor', $Value);
                $brightness = (int) $this->GetValue('AlarmStateBrightness');
                $this->SetAlarmStateSignalLamp(3, $Value, $brightness);
                break;

            case 'AlarmStateBrightness':
                $this->SetValue('AlarmStateBrightness', $Value);
                $color = (int) $this->GetValue('AlarmStateColor');
                $this->SetAlarmStateSignalLamp(3, $color, $Value);
                break;

            case 'NightMode':
                $this->ToggleNightMode($Value);
                break;

        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function RegisterProperties(): void
    {
        $this->RegisterPropertyString('Note', '');
        $this->RegisterPropertyBoolean('MaintenanceMode', false);
        // Visibility
        $this->RegisterPropertyBoolean('EnableSystemStateColor', true);
        $this->RegisterPropertyBoolean('EnableSystemStateBrightness', true);
        $this->RegisterPropertyBoolean('EnableDoorWindowStateColor', true);
        $this->RegisterPropertyBoolean('EnableDoorWindowStateBrightness', true);
        $this->RegisterPropertyBoolean('EnableAlarmStateColor', true);
        $this->RegisterPropertyBoolean('EnableAlarmStateBrightness', true);
        $this->RegisterPropertyBoolean('EnableNightMode', true);
        // System state
        $this->RegisterPropertyString('SystemStateSignalLamps', '[]');
        // Door and window state
        $this->RegisterPropertyString('DoorWindowStateSignalLamps', '[]');
        // Alarm state
        $this->RegisterPropertyString('AlarmStateSignalLamps', '[]');
    }

    private function CreateProfiles(): void
    {
        $profile = 'SIGL.' . $this->InstanceID . '.Color';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Bulb', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Blau', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, 'Grün', 'Bulb', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Türkis', 'Bulb', 0x01DFD7);
        IPS_SetVariableProfileAssociation($profile, 4, 'Rot', 'Bulb', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 5, 'Violett', 'Bulb', 0xB40486);
        IPS_SetVariableProfileAssociation($profile, 6, 'Gelb', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 7, 'Weiß', 'Bulb', 0xFFFFFF);
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['Color'];
        if (!empty($profiles)) {
            foreach ($profiles as $profile) {
                $profileName = 'SIGL.' . $this->InstanceID . '.' . $profile;
                if (IPS_VariableProfileExists($profileName)) {
                    IPS_DeleteVariableProfile($profileName);
                }
            }
        }
    }

    private function RegisterVariables(): void
    {
        $colorProfileName = 'SIGL.' . $this->InstanceID . '.Color';
        // System state
        // Color
        $this->RegisterVariableInteger('SystemStateColor', 'Systemstatus Farbe', $colorProfileName, 10);
        $this->EnableAction('SystemStateColor');
        $this->SetValue('SystemStateColor', 0);
        // Brightness
        $this->RegisterVariableInteger('SystemStateBrightness', 'Systemstatus Helligkeit', '~Intensity.100', 20);
        $this->EnableAction('SystemStateBrightness');
        $this->SetValue('SystemStateBrightness', 10);
        // Door and window state
        // Color
        $this->RegisterVariableInteger('DoorWindowStateColor', 'Tür- /Fensterstatus Farbe', $colorProfileName, 30);
        $this->EnableAction('DoorWindowStateColor');
        $this->SetValue('DoorWindowStateColor', 0);
        // Brightness
        $this->RegisterVariableInteger('DoorWindowStateBrightness', 'Tür- / Fensterstatus Helligkeit', '~Intensity.100', 40);
        $this->EnableAction('DoorWindowStateBrightness');
        $this->SetValue('DoorWindowStateBrightness', 10);
        // Alarm state
        // Color
        $this->RegisterVariableInteger('AlarmStateColor', 'Alarmstatus Farbe', $colorProfileName, 50);
        $this->EnableAction('AlarmStateColor');
        $this->SetValue('AlarmStateColor', 0);
        // Brightness
        $this->RegisterVariableInteger('AlarmStateBrightness', 'Alarmstatus Helligkeit', '~Intensity.100', 60);
        $this->EnableAction('AlarmStateBrightness');
        $this->SetValue('AlarmStateBrightness', 10);
        // Night mode
        $this->RegisterVariableBoolean('NightMode', 'Nachtmodus', '~Switch', 70);
        $this->EnableAction('NightMode');
        IPS_SetIcon($this->GetIDForIdent('NightMode'), 'Moon');
    }

    private function SetOptions(): void
    {
        // System state
        // Color
        $id = $this->GetIDForIdent('SystemStateColor');
        $use = $this->ReadPropertyBoolean('EnableSystemStateColor');
        IPS_SetHidden($id, !$use);
        // Brightness
        $id = $this->GetIDForIdent('SystemStateBrightness');
        $use = $this->ReadPropertyBoolean('EnableSystemStateBrightness');
        IPS_SetHidden($id, !$use);
        // Door and window state
        // Color
        $id = $this->GetIDForIdent('DoorWindowStateColor');
        $use = $this->ReadPropertyBoolean('EnableDoorWindowStateColor');
        IPS_SetHidden($id, !$use);
        // Brightness
        $id = $this->GetIDForIdent('DoorWindowStateBrightness');
        $use = $this->ReadPropertyBoolean('EnableDoorWindowStateBrightness');
        IPS_SetHidden($id, !$use);
        // Alarm state
        // Color
        $id = $this->GetIDForIdent('AlarmStateColor');
        $use = $this->ReadPropertyBoolean('EnableAlarmStateColor');
        IPS_SetHidden($id, !$use);
        // Brightness
        $id = $this->GetIDForIdent('AlarmStateBrightness');
        $use = $this->ReadPropertyBoolean('EnableAlarmStateBrightness');
        IPS_SetHidden($id, !$use);
        // Night mode
        $id = $this->GetIDForIdent('NightMode');
        $use = $this->ReadPropertyBoolean('EnableNightMode');
        IPS_SetHidden($id, !$use);
    }

    private function CheckMaintenanceMode(): bool
    {
        $result = false;
        $status = 102;
        if ($this->ReadPropertyBoolean('MaintenanceMode')) {
            $result = true;
            $status = 104;
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wartungsmodus ist aktiv!', 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', Abbruch, der Wartungsmodus ist aktiv!', KL_WARNING);
        }
        $this->SetStatus($status);
        IPS_SetDisabled($this->InstanceID, $result);
        return $result;
    }
}