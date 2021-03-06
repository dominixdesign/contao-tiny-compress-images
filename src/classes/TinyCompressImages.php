<?php

/**
 * Copyright (C) 2015-2016 Christian Barkowsky
 *
 * @author  Christian Barkowsky <hallo@christianbarkowsky.de>
 * @copyright Christian Barkowsky <http://christianbarkowsky.de>
 * @package tiny-compress-images
 * @license LGPL
 */


namespace Barkowsky;


use Contao\System;
use Contao\FilesModel;
use Contao\Request;


/**
 * Class TinyCompressImages
 * @package Barkowsky
 */
class TinyCompressImages extends System
{
    /**
     * Compress images
     *
     * @param boolean $arrFiles File array
     */
    public function processPostUpload($arrFiles)
    {
        if (is_array($arrFiles) && $GLOBALS['TL_CONFIG']['tinypng_api_key'] != '') {

            $strUrl = 'https://api.tinypng.com/shrink';
            $strKey = $GLOBALS['TL_CONFIG']['tinypng_api_key'];
            $strAuthorization = 'Basic '.base64_encode("api:$strKey");

            foreach($arrFiles as $file) {
                $objFile = FilesModel::findByPath($file);

                if (in_array($objFile->extension, array('png', 'jpg', 'jpeg'))) {

                    $strFile = TL_ROOT . '/' . $file;

                    $objRequest = new Request();
                    $objRequest->method = 'post';
                    $objRequest->data = file_get_contents($strFile);
                    $objRequest->setHeader('Content-type', 'image/png');
                    $objRequest->setHeader('Authorization', $strAuthorization);
                    $objRequest->send($strUrl);

                    $arrResponse = json_decode($objRequest->response);

                    if ($objRequest->code == 201) {
                        file_put_contents($strFile, fopen($arrResponse->output->url, "rb", false));

                        $objFile->tstamp = time();
                        $objFile->path   = $file;
                        $objFile->hash   = md5_file(TL_ROOT . '/' . $file);
                        $objFile->save();

                        System::log('Compression was successful. (File: ' . $file . ')', __METHOD__, TL_FILES);
                    } else {
                        System::log('Compression failed. (' . $arrResponse->message . ') (File: ' . $file . ')', __METHOD__, TL_FILES);
                    }
                }
            }
        }
    }
}
