<?php
namespace Admin\Controller;

class IndexCOntroller extends GlobalController {


    public function EditorImgUpload(){
        $re = ['' => E_SYSTEM ];
        $head = C('PREURL');

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path."BackendTest/Editor/";
        $conf = array(
            'pre' => 'index',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );

        $upload_res = \Common\_Upload($field,$realpath,$conf);

        if( $upload_res['state'] != 0 ){
            $re =  json_encode($upload_res);
            goto END;
        }

        foreach ($upload_res['file'] as $key => $value) {

            $file_path = $value['savepath'].$value['savename'];

            $path = $realpath.$value['savename'];

            $thumb = \Common\_Thumb($file_path,$value['savename']);

            if( $thumb['status'] != 0 ){
                $ret =  $thumb;
                goto END;
            }

            $thumbpath = $realpath.$thumb['savename'];
        }

        $re[ 'errno' ] = E_OK;
        $re[ 'data' ][] = $head.$path;

END:
        echo json_encode($re);
    }



}

?>