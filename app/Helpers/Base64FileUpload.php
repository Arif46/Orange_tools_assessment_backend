<?php

namespace App\Helpers;

use Validator;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

/**
 * Class FileUpload is simple class for handling file upload
 *
 * Class FileUpload is class that includes methods to create directory
 * to upload files, set permission to this directory, upload files and
 * images and delete this file path
 *
 * @package App\Helpers
 */
class Base64FileUpload
{
    /**
     * Create directory and set permission
     *
     * @param string $dir The directory in which file to be uploaded
     * @return void
     */
    private static function checkDirectory($dir)
    {
        File::makeDirectory(storage_path($dir), 0777, true, true);
    }

    /**
     * Upload file to specified folder
     *
     * @param object $request
     * @param string $fieldName The name of input file field
     * @param string $destinationPath The name of upload path
     * @param string $oldFile The value of db table column default null
     * @return array
     */
    public static function multipleUploadFile($file, $destinationPath, $oldFile = null)
    {
        if (!Str::startsWith($destinationPath, '/')) {
            $destinationPath = '/' . $destinationPath;
        }

        $finalDestinationPath = 'app/public' . $destinationPath;

        Self::checkDirectory($destinationPath);

        if ($oldFile != null) {
            Self::deleteFile($oldFile);
        }

        // $ext = explode('/', mime_content_type($request->$fieldName))[1];
        $ext = $file->extension();
        $fileName = uniqid().'-'.rand(100000, 999999) . time() . '.' . $ext;
        $file->move(storage_path($finalDestinationPath), $fileName);

        return $destinationPath .'/'. $fileName;

    }

    public static function uploadFile($request, $fieldName, $destinationPath, $oldFile = null)
    {
        /*  if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $fieldName)) {
            return ['success' => false, 'message' => 'File not uploaded'];
        } */


        if (!$fieldName) {
            return ['success' => false, 'message' => 'File not uploaded'];
        }

        if (!Str::startsWith($destinationPath, '/')) {
            $destinationPath = '/' . $destinationPath;
        }

        $finalDestinationPath = 'app/public' . $destinationPath;
        Self::checkDirectory($finalDestinationPath);

        if ($oldFile != null) {
            Self::deleteFile($oldFile);
        }

        // $ext = explode('/', mime_content_type($request->$fieldName));

        $file_parts = explode(";base64,", $request[$fieldName]);
        $file_type_aux = explode("/", $file_parts[0]);
        $ext = $file_type_aux[1];
        // return $ext;
        if ($ext == 'pdf') {
            $appData = 'application/pdf';
        } else if ($ext == 'vnd.ms-excel') {
            $ext = 'xls';
            $appData = 'application/vnd.ms-excel';
        } else if ($ext == 'vnd.openxmlformats-officedocument.wordprocessingml.document') {
            $ext = 'docx';
            $appData = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        } else if ($ext == 'csv') {
            $appData = 'text/csv';
        }

        // $fileName = rand(100000, 999999) . time() . '.' . $ext;
        // $mainFile->move(storage_path($finalDestinationPath), base64_decode($fileName));

        $mainFile = $request->$fieldName;

        $image = str_replace('data:'.$appData.';base64,', '', $mainFile);
        $image = str_replace(' ', '+', $image);
        $filePath = 'storage'.$destinationPath.'/'. uniqid() . '.'.$ext;
        file_put_contents($filePath, base64_decode($image));

        return [
            'success'   => true,
            'message'   => 'File uploaded',
            'data'      => $filePath
        ];
    }

    /**
     * Upload image to specified folder
     *
     * @param object $request
     * @param string $fieldName The name of input file field
     * @param string $destinationPath The name of upload path
     * @param mixed|string|null $oldFile The value of db table column default null
     * @param int $width The width of expected image
     * @param mixed|int|bool $height The height of expected image default null
     * @return array
     */
    public static function uploadImage($request, $fieldName, $destinationPath = '/uploads', $oldFile = null, $width = null, $height = null )
    {
        /**
         * storage folder && public folder file permission dite hbe
         */
        if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $fieldName)) {
            return [
                'success' => false,
                'message' => 'File is not provided'
            ];
        }
        $image = $request[$fieldName];

        $image_parts = explode(";base64,", $image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $ext = $image_type_aux[1];

        $image = str_replace('data:image/'.$ext.';base64,', '', $image);
        $file  = str_replace(' ', '+', $image);

        $finalDestinationPath = 'app/public' . $destinationPath;


        Self::checkDirectory($finalDestinationPath);

        $imageResize = Image::make(base64_decode($file));
        $name = rand(100000, 999999) . time() . '.' . $ext;

        if (!empty($width) && !empty($height)) {

            $orgWidth  = $imageResize->width();
            $orgHeight = $imageResize->height();

            if ($orgWidth >= $orgHeight) {
                $imageResize->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $imageResize->resize(null, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
        }
        $imageResize->save(storage_path("{$finalDestinationPath}/{$name}"));

        if ($oldFile != null) {
            Self::deleteFile($oldFile);
        }

        $imageFile = $destinationPath. '/' . $name;
        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $imageFile
        ];

    }


    public static function imageUpload($file, $destinationPath, $oldFile = null, $width = null, $height = null, $ext)
    {
        $finalDestinationPath = 'app/public' . $destinationPath;
        Self::checkDirectory($finalDestinationPath);

        $imageResize = Image::make(base64_decode($file));

        $name = rand(100000, 999999) . time() . '.' . $ext;

        if (!empty($width) && !empty($height)) {

            $orgWidth=$imageResize->width();
            $orgHeight=$imageResize->height();

            if ($orgWidth >= $orgHeight) {
                $imageResize->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $imageResize->resize(null, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
        }

        $imageResize->save(storage_path("{$finalDestinationPath}/{$name}"));

        if ($oldFile != null) {
            Self::deleteFile($oldFile);
        }

        return  $destinationPath. '/' . $name;
    }

    /**
     * Delete the file path
     *
     * @param string $oldFile The file path to be deleted
     * @return void
     */
    public static function deleteFile($oldFile)
    {
        // $finalDestinationPath = storage_path('app/public' . $oldFile);
        $finalDestinationPath = $oldFile;
        if (file_exists($finalDestinationPath)) {
            File::delete($finalDestinationPath);
        }
    }

     /**
     * base64 File Validation
     */
    public static function base64_file_validation ($file, $request_file_name, $fileSize = null) {
        $validator = Validator::make([], []); // Empty data and rules fields

        try {
            $allow = ['pdf', 'vnd.ms-excel', 'vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $extension = explode('/', mime_content_type($file))[1];

            if ($fileSize) {
                $size = strlen(base64_decode($file));
                $size_kb = $size / 1024;
                if ($size_kb > (int)$fileSize) {
                    $fileMb = (int)$fileSize > 1024 ? ((int)$fileSize / 1024).' MB' : $fileSize. 'KB';

                    return ([
                        'success' => false,
                        'message' => 'Not validate file',
                        'errors' => $validator->errors()->add($request_file_name, 'Maximum file size must be '.$fileMb)
                    ]);
                }
            }

            $isExist = collect($allow)->contains($extension);
            if (!$isExist) {
                $name = str_replace('_', ' ', $request_file_name);
                $message = 'The '.$name.' must be a file of type: pdf, xls, xlsx, doc, docx, csv.';
                $error = $validator->errors()->add($request_file_name, $message);

                return ([
                    'success' => false,
                    'message' => 'Not validate file',
                    'errors' => $error
                ]);
            } else {
                return (['success' => true, 'extension' => $extension]);
            }
        } catch (\Exception $e) {
            return ([
                'success' => false,
                'message' => 'Not validate File',
                'errors' => $validator->errors()->add($request_file_name, 'Not validate File')
            ]);
        }
    }
    /**
     * base64 IMage Validation
     */
    public static function base64ImageValidation ($request, $fieldName, $fileSize = null)
    {
        $validator = Validator::make([], []); // Empty data and rules fields

        try {
            $image = $request[$fieldName];
            $allow = ['jpeg', 'jpg', 'png', 'gif', 'svg'];
            $extension = explode('/', mime_content_type($image))[1];

            // checking file size
            if ($fileSize) {
                $size = strlen(base64_decode($image));
                $size_kb = $size / 1024;

                if ($size_kb > (int)$fileSize) {
                    $fileMb = (int)$fileSize > 1024 ? ((int)$fileSize / 1024).' MB' : $fileSize. 'KB';

                    return ([
                        'success' => false,
                        'message' => 'Not validate Image',
                        'errors' => $validator->errors()->add($fieldName, 'Maximum file size must be '.$fileMb)
                    ]);
                }
            }

            $isExist = collect($allow)->contains($extension);

            if (!$isExist) {
                $name = str_replace('_', ' ', $fieldName);
                $message = 'The '.$name.' must be a file of type: png, gif, jpeg, jpg.';

                return ([
                    'success' => false,
                    'message' => 'Not validate Image',
                    'errors' => $validator->errors()->add($fieldName, $message)
                ]);
            } else {
                return (['success' => true, 'extension' => $extension]);
            }
        } catch (\Exception $e) {
            return ([
                'success' => false,
                'message' => 'Not validate Image',
                'errors' => $validator->errors()->add($fieldName, 'Not validate Image')
            ]);
        }
    }
}
