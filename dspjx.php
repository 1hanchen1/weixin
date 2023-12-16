<?php
header('Content-Type:application/json; charset=utf-8');
$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : "";//需要解析的链接
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";//返回格式，默认json，可选down
if (empty($url)) {
    die(
        json_encode(
            array(
            'code' => 400,
            'msg' => '请输入需要解析的视频地址(只支持快手/抖音/皮皮虾/西瓜/最右/微视/绿州/微博视频)'
        ),480)
);
//根据视频链接自动判断
}elseif(strstr($url, 'kuaishou.com')||strstr($url, 'kuaishouapp.com')){
  $types = "kuaishou";    
}elseif(strstr($url, 'douyin.com')){
  $types = "douyin";    
}elseif(strstr($url, 'pipix.com')){
  $types = "pipixia";    
}elseif(strstr($url, 'ixigua.com')){
  $types = "xigua";    
}elseif(strstr($url, 'oasis.weibo.cn')){
  $types = "lvzhou";    
}elseif(strstr($url, 'izuiyou.com')){
  $types = "zuiyou";    
}elseif(strstr($url, 'weishi.qq.com')){
  $types = "weishi";    
}elseif(strstr($url, 'weibo.com')){
  $types = "weibo";    
}else{
    die(
        json_encode(
            array(
            'code' => 400,
            'msg' => '你输入的视频链接不正确呢(只支持快手/抖音/皮皮虾/西瓜/最右/微视/绿州/微博视频)'
        ),480)
    );    
}
$urls = $types($url,$type);
//快手解析实例(无水印) 已测试通过
function kuaishou($url,$type){
    $locs = get_headers($url, true);
    if(is_array($locs['Location'])) {
        $locs=$locs['Location'][count($locs['Location'])-1];
    }else{
        $locs=$locs['Location'];
    }
    preg_match('/photoId=(.*?)\&/', $locs, $matches);
$headers = array(
    'Cookie: did=web_'.md5($locs.time()).';',
    'Referer: '.$locs, 
    'Content-Type: application/json'
    );
    $post_data = '{"photoId": "'.$matches[1].'","isLongVideo": false}';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://v.m.chenzhongtech.com/rest/wd/photo/info');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_NOBODY, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $data = curl_exec($curl);
    curl_close($curl);
    $json = json_decode($data, true);
    if ($type != "down") {
    if($json['photo']['mainMvUrls'][key($json['photo']['mainMvUrls'])]['url']){
        die(
        json_encode(
            array(
                'code' => 200,
                'msg' => '解析成功！',
                'data' => [
                    'avatar' => $json['photo']['headUrl'],
                    'author' => $json['photo']['userName'],
                    //'time'   => $video_time[1],
                    'title'  => $json['photo']['caption'],
                    'cover'  => $json['photo']['coverUrls'][key($json['photo']['coverUrls'])]['url'],
                    'videourl' => $json['photo']['mainMvUrls'][key($json['photo']['mainMvUrls'])]['url'],
                ],
                'text' => [
                'msg' => '当前是快手解析'
                ,'copyright'  => '短视频解析接口'
                ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
            ),480)
        );
    }
    }else{
    header("Location:".$json['photo']['mainMvUrls'][key($json['photo']['mainMvUrls'])]['url']);
    die;
    } 
    }
//抖音解析实例(无水印) 已测试通过
function douyin($url,$type){
$loc = get_headers($url, true)['Location'][1];
preg_match('/video\/(.*)\?/', $loc, $id);
$arr = json_decode(curl('https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . $id[1]),true);
preg_match('/href="(.*?)">Found/',curl(str_replace('playwm', 'play',$arr['item_list'][0]["video"]["play_addr"]["url_list"][0])),$matches);
$video_url = str_replace('&','&', $matches[1]);
if ($type != "down") {
if($video_url){
    die(
    json_encode(
        array(
            'code' => 200,
            'msg' => '解析成功！',
            'data' => [
            'author' => $arr['item_list'][0]['author']['nickname'],
            'uid'    => $arr['item_list'][0]['author']['unique_id'],
            'avatar' => $arr['item_list'][0]['author']['avatar_larger']['url_list'][0],
            'like'   => $arr['item_list'][0]['statistics']['digg_count'],
            'time'   => $arr['item_list'][0]["create_time"],
            'title'  => $arr['item_list'][0]['share_info']['share_title'],
            'cover'  => $arr['item_list'][0]['video']['origin_cover']['url_list'][0],
            'videourl' => $video_url,
            ],
            'music'  => [
                'author' => $arr['item_list'][0]['music']['author'],
                'avatar' => $arr['item_list'][0]['music']['cover_large']['url_list'][0],
                'url'    => $arr['item_list'][0]['music']['play_url']['url_list'][0],
            ],
            'text' => [
            'msg' => '当前是抖音解析'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())
            ]
        ),480)
    );
}
}else{
    header("Location:$video_url");
    die;
}
}
//皮皮虾解析实例(无水印) 已测试通过
function pipixia($url,$type){
$loc = get_headers($url, true)['Location'];
preg_match('/item\/(.*)\?/', $loc, $id);
$arr = json_decode(curl('https://is.snssdk.com/bds/cell/detail/?cell_type=1&aid=1319&app_name=super&cell_id=' . $id[1]),true);
$video_url = $arr['data']['data']['item']['origin_video_download']['url_list'][0]['url'];
if ($type != "down") {
if($video_url){
    die(
    json_encode(
        array(
            'code' => 200,
            'msg' => '解析成功！',
            'data' => [
            'author' => $arr['data']['data']['item']['author']['name'],
            'avatar' => $arr['data']['data']['item']['author']['avatar']['download_list'][0]['url'],
            'time'   => $arr['data']['data']['display_time'],
            'title'  => $arr['data']['data']['item']['content'],
            'cover'  => $arr['data']['data']['item']['cover']['url_list'][0]['url'],
            'videourl' => str_replace('http://', 'https://',$video_url),
            ],
            'text' => [
            'msg' => '当前是皮皮虾视频解析'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
        ),480)
    );
}
}else{
    header("Location:$video_url");
    die;
}
}
//西瓜视频解析实例(无水印) 已测试通过
function xigua($url,$type){
if (strpos($url, 'v.ixigua.com') != false) {
$loc = get_headers($url, true)['Location'][0];
$url='https://www.ixigua.com/'.explode('.com/video/',$loc)[1];
}
$headers = [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36 ",
    "cookie:" //需要写入你的西瓜视频的cookie才能完成解析
];
$text = curl($url,$headers);
preg_match('/<script id=\"SSR_HYDRATED_DATA\">window._SSR_HYDRATED_DATA=(.*?)<\/script>/', $text, $jsondata);
$data = json_decode(str_replace('undefined', 'null', $jsondata[1]), 1);
$result = $data["anyVideo"]["gidInformation"]["packerData"]["video"];
$video = $result["videoResource"]["dash"]["dynamic_video"]["dynamic_video_list"][2]["main_url"];
$music = $result["videoResource"]["dash"]["dynamic_video"]["dynamic_audio_list"][0]["main_url"];
$video_author = $result['user_info']['name'];
$video_avatar = str_replace('300x300.image', '300x300.jpg', $result['user_info']['avatar_url']);
$video_cover = $data["anyVideo"]["gidInformation"]["packerData"]["video"]["poster_url"];
$video_title = $result["title"];
if ($type != "down") {
if($video){
    die(
    json_encode(
        array(
        'code' => 200,
        'msg'  => '解析成功',
        'data' => [
            'author' => $video_author,
            'avatar' => $video_avatar,
            'time'   => $result['video_publish_time'],
            'title'  => $video_title,
            'cover'  => $video_cover,
            'videourl'    => base64_decode($video), 
            'music'  => [
                'url' => base64_decode($music),
               ]
            ],
            'text' => [
            'msg' => '当前是西瓜视频解析'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
        ),480)
    );
}
}else{
    header("Location:".base64_decode($video));
    die;
}
}
//绿洲短视频解析接口实例(无水印) 已测试通过
function lvzhou($url,$type){
$text = curl($url);
preg_match('/<div class=\"status-title\">(.*)<\/div>/', $text, $video_title);
preg_match('/<div style=\"background-image:url\((.*)\)/', $text, $video_cover);
preg_match('/<video src=\"([^\"]*)\"/', $text, $video_url);
preg_match('/<div class=\"nickname\">(.*)<\/div>/', $text, $video_author);
preg_match('/<a class=\"avatar\"><img src=\"(.*)\?/', $text, $video_author_img);
if ($type != "down") {
if($video_url[1]){
    die(
    json_encode(
        array(
        'code' => 200,
        'msg'  => '解析成功',
        'data' => [
            'author' => $video_author[1],
            'avatar' => str_replace('1080.180', '1080.680', $video_author_img)[1],
            'title'  => $video_title[1],
            'cover'  => $video_cover[1],
            'videourl' => str_replace('amp;', '',$video_url[1]),
            ],
            'text' => [
            'msg' => '当前是绿州短视频解析接口'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
        ),480)
    );
}
}else{
    header("Location:".str_replace('amp;', '',$video_url[1]));
    die;
}
}
//最右短视频解析接口实例(无水印) 已测试通过
function zuiyou($url,$type){
$text = curl($url);
preg_match('/fullscreen=\"false\" src=\"(.*?)\"/', $text, $video);
preg_match('/content":"(.*?)"/', $text, $video_title);
preg_match('/poster=\"(.*?)\">/', $text, $video_cover);
$video_url = str_replace('\\', '/', str_replace('u002F', '', $video[1]));
preg_match('/<span class=\"SharePostCard__name\">(.*?)<\/span>/', $text, $video_author);
if ($type != "down") {
if($video_url[1]){
    die(
    json_encode(
        array(
        'code' => 200,
        'msg'  => '解析成功',
        'data' => [
            'author' => $video_author[1],
            'title'  => $video_title[1],
            'cover' => $video_cover[1],
            'videourl' => $video_url,
            ],
            'text' => [
            'msg' => '当前是最右短视频解析接口'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
        ),480)
    );
}
}else{
    header("Location:$video_url");
    die;
}
}
//微视短视频解析接口实例(无水印) 已测试通过
function weishi($url,$type){
preg_match('/feed\/(.*)\b/',$url, $id);
$arr = json_decode(curl('https://h5.weishi.qq.com/webapp/json/weishi/WSH5GetPlayPage?feedid=' . $id[1]),true);
$video_url = $arr['data']['feeds'][0]['video_url'];
if ($type != "down") {
if($video_url){
    die(
    json_encode(
        array(
        'code' => 200,
        'msg'  => '解析成功',
        'data' => [
            'author' => $arr['data']['feeds'][0]['poster']['nick'],
            'avatar' => $arr['data']['feeds'][0]['poster']['avatar'],
            'time'   => $arr['data']['feeds'][0]['poster']['createtime'],
            'title'  => $arr['data']['feeds'][0]['feed_desc_withat'],
            'cover'  => $arr['data']['feeds'][0]['images'][0]['url'],
            'videourl'    => $video_url
            ],
            'text' => [
            'msg' => '当前是微视短视频解析接口'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
        ),480)
    );
}
}else{
    header("Location:$video_url");
    die;
}
}
//微博视频解析实例(无水印) 已测试通过
function weibo($url,$type){
if (strpos($url, 'show?fid=') != false) {
    preg_match('/fid=(.*)/', $url, $id);
    $arr = json_decode(weibo_curl($id[1]), true);
}else{
    preg_match('/\d+\:\d+/', $url, $id);
    $arr = json_decode(weibo_curl($id[0]), true);
}
$video_url = $arr['data']['Component_Play_Playinfo']['urls'];
if ($type != "down") {
if($video_url){
    die(
    json_encode(
        array(
        'code' => 200,
        'msg'  => '解析成功',
        'data' => [
            'author' => $arr['data']['Component_Play_Playinfo']['author'],
            'avatar' => $arr['data']['Component_Play_Playinfo']['avatar'],
            'time'   => $arr['data']['Component_Play_Playinfo']['real_date'],
            'title'  => $arr['data']['Component_Play_Playinfo']['title'],
            'cover'  => $arr['data']['Component_Play_Playinfo']['cover_image'],
            'videourl'  => $video_url //$arr['data']['Component_Play_Playinfo']['urls'][key($arr['data']['Component_Play_Playinfo']['urls'])]
            ],
            'text' => [
            'msg' => '当前是微博视频解析'
            ,'copyright'  => '短视频解析接口'
            ,'time'=>'当前解析时间为：'.date('Y-m-d H:i:s',time())]
        ),480)
    );
}
}else{
    $video_url = $arr['data']['Component_Play_Playinfo']['urls'][key($arr['data']['Component_Play_Playinfo']['urls'])];
    header("Location:$video_url");
    die;
}
}
//解析视频需要的参数 请勿乱动！
function curl($url, $headers = []){
    $header = ['User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1'];
    $con = curl_init((string)$url);
    curl_setopt($con, CURLOPT_HEADER, false);
    curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
    if (!empty($headers)) {
        curl_setopt($con, CURLOPT_HTTPHEADER, $headers);
    } else {
        curl_setopt($con, CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($con, CURLOPT_TIMEOUT, 5000);
    $result = curl_exec($con);
    return $result;
}
//微博视频解析需要的参数 请勿乱动！
function weibo_curl($id){
    //自动更新微博临时cookie 请勿乱改动！
    $data = ["cb" => "gen_callback","fp" => '{"os"=>"1","browser"=>"Chrome95,0,4621,0","fonts"=>"undefined","screenInfo"=>"1920*1080*24","plugins"=>"Portable Document Format::internal-pdf-viewer::Chromium PDF Plugin|::mhjfbmdgcfjbbpaeojofohoefgiehjai::Chromium PDF Viewer"}',];
    $data_tid = curl_request('https://passport.weibo.com/visitor/genvisitor',$data);
    $tid = str_replace(['window.gen_callback && gen_callback(',');'],'',$data_tid);
    if(strstr($tid, '+')){
        $tid = str_replace('+', '%2B', $tid);
    }
    $data_sub = curl_request('https://passport.weibo.com/visitor/visitor?a=incarnate&t='.json_decode($tid, true)['data']['tid'].'&w=2&c=095&gc=&cb=cross_domain&from=weibo&_rand=0.34268151967150073');
    $sub = str_replace(['window.cross_domain && cross_domain(',');'],'',$data_sub);
    $cookie =  'SUB='.json_decode($sub, true)['data']['sub']; //自动获取微博临时cookie 避免被禁止访问;
    $post_data = "data={\"Component_Play_Playinfo\":{\"oid\":\"$id\"}}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://weibo.com/tv/api/component?page=/tv/show/" . $id);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_REFERER, "https://weibo.com/tv/show/" . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
//自动获取微博cookie需要的参数 请勿乱动！
function curl_request($url,$data = null,$user_agent = null) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_USERAGENT,$user_agent);
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}