<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use FFMpeg;
use File;
use Storage;


class StegnographyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        return response()->json(['data' => 'Api Hit Checking']);
    }

    public function ImageEncodeCrypt(Request $request)
    {        
        $validator = Validator::make($request->all(), [
            'msg' => 'required',
            'password' => 'required',
            'encImage' => 'required'
        ]);
        if($validator->passes())
        {   
            $pictures = $request->encImage;
            $pictures = base64_encode(file_get_contents($request->file('encImage')));
            $pictures = str_replace('data:image/png;base64,', '', $pictures);
            $pictures = str_replace(' ', '+', $pictures);
            
            $original = preg_replace('/data:image\/\w+;base64,/', '', $pictures);
            $original = base64_decode($original);
            $imageOriginal = imagecreatefromstring($original);
            $x_dimension = imagesx($imageOriginal); //height
            $y_dimension = imagesy($imageOriginal); //width
            
            $key = $request->password;
            $imageCrypto = $imageOriginal;
            $string =  $request->msg;
            
            $stringCount = strlen($string);
            
            $iv = "1234567812345678";
            
            $stringCrypto = openssl_encrypt($string, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);
            $bin = $this->textBinASCII2($stringCrypto); //string to array
            
            $stringLength = $this->textBinASCII2((string)strlen($bin));
            
            $sign = $this->textBinASCII2('uzairsaeed');
            
            $binaryText = str_split($stringLength.$sign.$bin);
            $textCount = count($binaryText);
            $count = 0;
            
            
            for ($x = 0; $x < $x_dimension; $x++) {

                if ($count >= $textCount)
                    break;

                for ($y = 0; $y < $y_dimension; $y++) {

                    if ($count >= $textCount)
                        break;  

                    $rgbOriginal = imagecolorat($imageOriginal, $x, $y);
                    $r = ($rgbOriginal >> 16) & 0xFF;
                    $g = ($rgbOriginal >> 8) & 0xFF;
                    $b = $rgbOriginal & 0xFF;
                    
                    $blueBinaryArray = str_split((string)base_convert($b,10,2));
                    $blueBinaryArray[count($blueBinaryArray)-1] = $binaryText[$count];
                    $blueBinary = implode($blueBinaryArray);
                    
                    $color = imagecolorallocate($imageOriginal, $r, $g,
                    bindec($blueBinary));
                    imagesetpixel($imageCrypto, $x, $y, $color);
                    
                    $count++;
                }
            }
           
            ob_start();
            imagepng($imageCrypto);
            $image_string = base64_encode(ob_get_contents());
            ob_end_clean();
            $base64 = $image_string;
            imagedestroy($imageCrypto);

            $base64 = base64_decode($base64);
            
            $imageName = str_random(10).uniqid().'.'.'png';
            $imagePath = public_path().'/'. $imageName;
            \File::put($imagePath , $base64);

            // dd($imagePath);
            
            return response()->json(['encImage' => $imagePath]);
        }
        else
        {
            return response()->json($validator->messages(), 200);
        }
        // dd($base64);
    }

    public function ImageDecodeCrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required',
            'password' => 'required'
        ]);

        if($validator->passes())
        {   
            $pictures = base64_encode(file_get_contents($request->file('data')));
            $original = preg_replace('/data:image\/\w+;base64,/', '', $pictures);
            $original = base64_decode($original);
            $imageOriginal = imagecreatefromstring($original);
            $x_dimension = imagesx($imageOriginal); //height
            $y_dimension = imagesy($imageOriginal); //width

            $binaryString = '';

            for ($x = 0; $x < $x_dimension; $x++) {

                for ($y = 0; $y < $y_dimension; $y++) {

                    $rgbOriginal = imagecolorat($imageOriginal, $x, $y);
                    
                    $b = $rgbOriginal & 0xFF;
                    
                    $blueBinaryArray = str_split((string)base_convert($b, 10, 2));
                    $bit = $blueBinaryArray[count($blueBinaryArray) - 1];
                    $binaryString .= $bit;
                }
            }
            
            $iv = "1234567812345678";
            $key = $request->password;
                        
            $sign = $this->textBinASCII2('uzairsaeed');
            $lengthSign = strlen($sign);
            $position = strpos($binaryString, $sign);
            
            $lengthBinData = mb_substr($binaryString, 0, $position);
            $lengthData = $this->stringBinToStringChars8($lengthBinData);
            $positionData = $position + $lengthSign;
            
            $binaryData = mb_substr($binaryString, $positionData, $lengthData);
            
            $cryptoString = $this->stringBinToStringChars8($binaryData);
            $output = openssl_decrypt($cryptoString, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);

            return response()->json(['text' => $output]);
        }
        else
        {
            return response()->json($validator->messages(), 200);
        }
    }

    public function stringBinToStringChars8($strBin)
    {
        $arrayChars = str_split($strBin, 8);
        $result = '';
        for ($i = 0; $i<count($arrayChars); $i++)
        {
            $result.=$this->ASCIIBinText2($arrayChars[$i]);
        }
        return $result;
    }
    
    function textBinASCII2($text)
    {
        $bin = array();
        $max = 0;
        for($i=0; strlen($text)>$i; $i++) {
            $bin[] = decbin(ord($text[$i]));
            if(strlen($bin[$i]) < 8)
            {
                $countNull = 8 - strlen($bin[$i]);
                $stringNull = '';
                for($j = 0; $j < $countNull; $j++) {
                    $stringNull .= '0';
                }
                $bin[$i] = $stringNull.$bin[$i];
            }
            if(strlen($bin[$i]) > 8 && strlen($bin[$i]) > $max)
            {
                $max = strlen($bin[$i]);
            }
        }
        return implode('',$bin);
    }
    
    function ASCIIBinText2($bin)
    {
        $text = array();
        $bin = explode(" ", $bin);
        for($i=0; count($bin)>$i; $i++)
            $text[] = chr(bindec($bin[$i]));
        return implode($text);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function VideoDecodeCrypt(Request $request){
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'encVideo' => 'required'
        ]);
        if($validator->passes())
        {
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => 'C:\ffmpeg\bin\ffmpeg.exe',
                'ffprobe.binaries'  => 'C:\ffmpeg\bin\ffprobe.exe',
                'timeout'          => 1360000, // The timeout for the underlying process
                'ffmpeg.threads'   => 16,   // The number of threads that FFMpeg should use
            ]);
            $vidz = $request->encVideo;
            $video = $ffmpeg->open($vidz);
            $uniqueid = uniqid();
            $frameName = "extrEncImg".$uniqueid.".jpeg";
            $video
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(4))
            ->save(public_path().'/uploads/'.$frameName );
            
            $extractedFrame =File::get(public_path().'/uploads/'.$frameName);

            $pictures = $extractedFrame;
            $imageOriginal = imagecreatefromstring($pictures);
            
            $x_dimension = imagesx($imageOriginal); //height
            $y_dimension = imagesy($imageOriginal); //width

            $binaryString = '';

            for ($x = 0; $x < $x_dimension; $x++) {

                for ($y = 0; $y < $y_dimension; $y++) {

                    $rgbOriginal = imagecolorat($imageOriginal, $x, $y);
                    
                    $b = $rgbOriginal & 0xFF;
                    
                    $blueBinaryArray = str_split((string)base_convert($b, 10, 2));
                    $bit = $blueBinaryArray[count($blueBinaryArray) - 1];
                    $binaryString .= $bit;
                }
            }
            
            $iv = "1234567812345678";
            $key = $request->password;
            // dd($key);
            
            $sign = $this->textBinASCII2('uzairsaeed');

            $lengthSign = strlen($sign);
            $position = strpos($binaryString, $sign);
            $lengthBinData = mb_substr($binaryString, 0, $position);
            $lengthData = $this->stringBinToStringChars8($lengthBinData);
            $positionData = $position + $lengthSign;
            $binaryData = mb_substr($binaryString, $positionData, $lengthData);
        
            $cryptoString = $this->stringBinToStringChars8($binaryData);
            $output = openssl_decrypt($cryptoString, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);
            return response()->json(['text' => $output]);

        }
        else
        {
            return response()->json($validator->messages(), 200);
        }

    }
    public function VideoEncodeCrypt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'msg' => 'required',
            'password' => 'required',
            'encVideo' => 'required'
        ]);
        if($validator->passes())
        {      
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => 'C:\ffmpeg\bin\ffmpeg.exe',
                'ffprobe.binaries'  => 'C:\ffmpeg\bin\ffprobe.exe',
                'timeout'          => 1360000, // The timeout for the underlying process
                'ffmpeg.threads'   => 16,   // The number of threads that FFMpeg should use
            ]);
            $vidz = $request->encVideo;
            $video = $ffmpeg->open($vidz);
            $extension = $vidz->extension();
            $uniqueid = uniqid();
            
            $frameName = "extrImg".$uniqueid.".jpeg";
            $video
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5))
            ->save(public_path().'/uploads/'.$frameName );

            $file = $request->file('encVideo');
            $videoFilename = 'vidUpl'. $uniqueid . $file->getClientOriginalName();
            $videoPath = public_path().'/uploads/';
            $file->move($videoPath, $videoFilename);
            $videoPathName = $videoPath. $videoFilename;
            
            $extractedFrame =File::get($videoPath.$frameName);
            $extractedFrame = base64_encode($extractedFrame);
            
            $pictures = $extractedFrame;
            $pictures = str_replace('data:image/png;base64,', '', $pictures);
            $pictures = str_replace(' ', '+', $pictures);
            $original = preg_replace('/data:image\/\w+;base64,/', '', $extractedFrame);
            $original = base64_decode($original);
            $imageOriginal = imagecreatefromstring($original);
            $x_dimension = imagesx($imageOriginal); //height
            $y_dimension = imagesy($imageOriginal); //width
            
            $key = $request->password;
            $imageCrypto = $imageOriginal;
            $string =  $request->msg;
            $stringCount = strlen($string);
            
            $iv = "1234567812345678";
            
            $stringCrypto = openssl_encrypt($string, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);
            $bin = $this->textBinASCII2($stringCrypto); //string to array
            
            $stringLength = $this->textBinASCII2((string)strlen($bin));
            
            $sign = $this->textBinASCII2('uzairsaeed');
            
            $binaryText = str_split($stringLength.$sign.$bin);
            $textCount = count($binaryText);
            $count = 0;
            
            
            for ($x = 0; $x < $x_dimension; $x++) {

                if ($count >= $textCount)
                    break;

                for ($y = 0; $y < $y_dimension; $y++) {

                    if ($count >= $textCount)
                        break;  

                    $rgbOriginal = imagecolorat($imageOriginal, $x, $y);
                    $r = ($rgbOriginal >> 16) & 0xFF;
                    $g = ($rgbOriginal >> 8) & 0xFF;
                    $b = $rgbOriginal & 0xFF;
                    
                    $blueBinaryArray = str_split((string)base_convert($b,10,2));
                    $blueBinaryArray[count($blueBinaryArray)-1] = $binaryText[$count];
                    $blueBinary = implode($blueBinaryArray);
                    
                    $color = imagecolorallocate($imageOriginal, $r, $g,
                    bindec($blueBinary));
                    imagesetpixel($imageCrypto, $x, $y, $color);
                    
                    $count++;
                }
            }

            $EcryptedVideoImageName = 'encVidImage'.uniqid().'.jpg'; 
            $imageSave = imagepng($imageCrypto,public_path().'/uploads/'.$EcryptedVideoImageName);
            $imagePath = public_path().'/uploads/'.$EcryptedVideoImageName;
            
            ob_start();
            imagepng($imageCrypto);
            $image_string = base64_encode(ob_get_contents());
            ob_end_clean();
            $base64 = $image_string;
            $base64 = 'data:image/png;base64,' . $image_string;
            imagedestroy($imageCrypto);

            $fmpeg = "C:\\ffmpeg\\bin\\ffmpeg";
            $filter = "[0:v][1:v] overlay=(main_w-overlay_w)/2:(main_h-overlay_h)/2:enable='between(t,5,1)'";
            $output = public_path().'/uploads/'.'encVideo'.$uniqueid.'.mp4';
            // $cmd = "$fmpeg -i $vidz -an -ss 1 AAAA.jpg ";
            $cmd = $fmpeg.' -i '.$videoPathName.' -i '.$imagePath.'  -filter_complex "'.$filter.'" -pix_fmt yuv420p -c:a copy '.$output;
             
            if(! shell_exec($cmd)){
                return response()->json(['encVideo' => $output]);    
            } else {
                return response()->json($validator->messages("FFmpeg didn't run this command"), 200);
            }
                        
            return response()->json(['encImage' => $base64]);

        }
        else
        {
            return response()->json($validator->messages(), 200);
        }
        // dd($base64);
    }
}
