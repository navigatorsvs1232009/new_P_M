<?php

use Bitrix\Main\Diag\Debug;
use \EventHandlers\CRM\CrmDeal;
use \Bitrix\Crm\DealTable;
use Bitrix\Mail\Helper\Message;
#use Bitrix\Mail\Integration\Calendar\ICal\ICalMailManager;
use \Bitrix\Main\Entity\Event;
#use \Bitrix\Main\EventResult;

CModule::IncludeModule('mail');
CModule::IncludeModule('crm');
CModule::IncludeModule('tasks');
$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler('mail', 'onMailMessageNew', 'onMailMessageNew');
function onMailMessageNew($event)
{
    global $USER;
    $USER_ID = $USER->GetID();
    $message = $event->getParameter('message');
    $file_id = Message::ensureAttachments($message);



    $dbr_attach = CMailAttachment::GetList(Array("NAME" => "ASC", "ID" => "ASC"), Array("MESSAGE_ID" => $message['ID']));
    while ($dbr_attach_arr = $dbr_attach->GetNext()) {
        if ($dbr_attach_arr["FILE_NAME"]=='1.tmp' ||
            preg_match_all('/\\.(?:exe|html|phtml|pl|js|htm|py|php|php4|php3|phtml|shtml)$/i', $dbr_attach_arr["FILE_NAME"], $p_matches, PREG_PATTERN_ORDER))
            continue;
        $attach_id = $dbr_attach_arr["ID"];
        $dbr = CMailAttachment::GetByID($attach_id);
        if($dbr_arr = $dbr->Fetch())
        {
            $fname =  $_SERVER['DOCUMENT_ROOT']."/upload/from_mail/".$dbr_attach_arr["FILE_NAME"];
            $handle = fopen($fname, 'wb');
            fwrite($handle, $dbr_arr["FILE_DATA"]);
            fclose($handle);
            $arFile = CFile::MakeFileArray($fname);
            //
        $storage = Bitrix\Disk\Driver::getInstance()->getStorageByUserId(1);
        $folder = $storage->getFolderForUploadedFiles();
        $file = $folder->uploadFile($arFile, array(
            'NAME' => $arFile["name"],
            'CREATED_BY' => 1
        ), array(), true);
        $FILE_ID = $file->getId();


            $arFile["old_file"] = "";
            $arFile["del"] = "Y";
            $arFile["MODULE_ID"] = "tasks";
            $fid[] = CFile::SaveFile($arFile, "tasks");
        }
    }


    //
    $oTaskItem = array(
        "TITLE" => $message["SUBJECT"],
        "DESCRIPTION" => $message["BODY"],
        "RESPONSIBLE_ID" => 1,
         "UF_TASK_WEBDAV_FILES" => Array("n$FILE_ID"),

    );
    $taskItem = \CTaskItem::add($oTaskItem, 1);
    //

    Debug::dumpToFile($FILE_ID);
    Debug::dumpToFile($fid);
    Debug::dumpToFile($arFile);


}



//$eventManager->addEventHandler('crm', 'onEntityDetailsTabsInitialized', [
//        'Aclips\\CustomCrm\\Handler',
//        'setCustomTabs'
//    ]
//);
