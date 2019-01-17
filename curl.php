<?php
$ch = curl_init();

//"job":"purchase_import",
//"file":"/tmp/file.txt"

$data = array("job" => "purchase_import", "file" => "./file_030718.txt");                                                                    
$data_string = urlencode(json_encode($data));

print urlencode('"file":"./file_030718.txt","job":"purchase_import"}');
exit();                                                                                   
                                                                                                                     
//$ch = curl_init('http://api.local/rest/users');                                                                      
//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                                                                                      
//$postdata = array('api_key' => 'a1b7826ec225f5d9a8382d6aa16dad97', 'sig' => '505badb8a25f554455701e5c68120e41', 'format' => 'json', 'json' => $data_string);
$postdata = array('api_key' => 'a1b7826ec225f5d9a8382d6aa16dad97', 'sig' => '7c4b1a1b5443612a8190fc5f8e86c10d', 'format' => 'json', 'json' => $data_string);
//curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

//curl 'https://api.sailthru.com/<endpoint>?api_key=<key>&sig=<sig>&format=<json_or_xml>&json=<url_escaped_data>'

echo http_build_query($postdata) . "\n";

curl_setopt($ch, CURLOPT_URL,"https://api.sailthru.com/job");
//curl_setopt($ch, CURLOPT_URL,"https://getstarted.sailthru.com/developers/api/job");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

// in real life you should use something like:
// curl_setopt($ch, CURLOPT_POSTFIELDS, 
//          http_build_query(array('postvar1' => 'value1')));

// receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($data_string))                                                                       
);   

$server_output = curl_exec ($ch);

curl_close ($ch);

$file = 'test.html';
file_put_contents($file, $server_output); //$server_output . "\n\n";

// further processing ....
if ($server_output == "OK") {
	print 'Processed';
} else {
	print 'Not Processed';
}

/*
7c4b1a1b5443612a8190fc5f8e86c10d
md5 -s '505badb8a25f554455701e5c68120e41a1b7826ec225f5d9a8382d6aa16dad97json{"job":"purchase_import","file":"/file.txt"}'
md5 -s 'abcsecret123keyjson{"id":"neil@example.com"}'
curl 'https://api.sailthru.com/job?api_key=a1b7826ec225f5d9a8382d6aa16dad97&sig=505badb8a25f554455701e5c68120e41&format=json&json=%257B%2522job%2522%253A%2522purchase_import%2522%252C%2522file%2522%253A%2522%255C%252Ffile.txt%2522%257D'


18178df0ea17782e63ecaafb9a052f23


md5 -s '505badb8a25f554455701e5c68120e41a1b7826ec225f5d9a8382d6aa16dad97json{"file":"/file.txt","job":"purchase_import"}'


curl -X POST https://api.sailthru.com/job -d 'api_key=a1b7826ec225f5d9a8382d6aa16dad97&sig=18178df0ea17782e63ecaafb9a052f23&format=json&json=%257B%2522job%2522%253A%2522purchase_import%2522%252C%2522file%2522%253A%2522%255C%252Ffile.txt%2522%257D'


curl -X POST https://api.sailthru.com/job -d 'api_key=a1b7826ec225f5d9a8382d6aa16dad97&sig=18178df0ea17782e63ecaafb9a052f23&format=json&json=%257B%2522file%2522%253A%2522%255C%252Ffile.txt%2522%252C%2522job%2522%253A%2522purchase_import%2522%257D'





md5 -s '505badb8a25f554455701e5c68120e41a1b7826ec225f5d9a8382d6aa16dad97json{"file":"./file_030718.txt","job":"purchase_import"}'

md5 -s '505badb8a25f554455701e5c68120e41a1b7826ec225f5d9a8382d6aa16dad97json%7B%22file%22%3A%22.%2Ffile_030718.txt%22%2C%22job%22%3A%22purchase_import%22%7D'

f91d2077e4a695a1ec848e6017d566a9

curl -X POST https://api.sailthru.com/job -d 'api_key=a1b7826ec225f5d9a8382d6aa16dad97&sig=f91d2077e4a695a1ec848e6017d566a9&format=json&json=%257B%2522file%2522%253A%2522.%252Ffile_030718.txt%2522%252C%2522job%2522%253A%2522purchase_import%2522%257D'



purchase_import



%257B%2522job%2522%253A%2522purchase_import%2522%252C%2522file%2522%253A%2522.%255C%252Ffile_030718.txt%2522%257D



%7B%22file%22%3A%22.%2Ffile_030718.txt%22%2C%22job%22%3A%22purchase_import%22%7D


*/
?>