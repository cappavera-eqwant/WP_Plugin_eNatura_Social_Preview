<?php
// Isolated contract tests. No WordPress installation or network requests.
define('ABSPATH', __DIR__);
class WP_Post { public $ID=7; public $post_type='post'; public $post_excerpt='Описание <b>записи</b>'; public $post_content=''; public $post_password=''; public $post_status='publish'; public $post_parent=0; }
$GLOBALS['opts']=['blog_public'=>1]; $GLOBALS['context']='single'; $GLOBALS['post']=new WP_Post(); $GLOBALS['images']=[];
function add_action(...$a){} function wp_parse_args($a,$b){return array_merge($b,$a);} function get_option($k,$d=false){return $GLOBALS['opts'][$k]??$d;}
function apply_filters($n,$v){return $v;} function is_admin(){return false;} function is_feed(){return false;} function is_trackback(){return false;} function is_embed(){return false;} function is_preview(){return $GLOBALS['context']==='preview';} function is_404(){return false;} function is_search(){return $GLOBALS['context']==='search';} function is_singular(){return in_array($GLOBALS['context'],['single','front','preview']);} function is_front_page(){return $GLOBALS['context']==='front';} function is_home(){return $GLOBALS['context']==='home';} function is_category(){return false;} function is_tag(){return false;} function is_tax(){return false;} function is_post_type_archive(){return false;}
function get_bloginfo($k){return ['name'=>'Реальный сайт','description'=>'Описание сайта','language'=>'ru-RU'][$k];} function strip_shortcodes($t){return preg_replace('/\[.*?\]/','',$t);} function wp_strip_all_tags($t,$b=false){return strip_tags(preg_replace('~<(script|style)[^>]*>.*?</\1>~s','',$t));} function wp_html_excerpt($t,$n,$more){return $t;} function wp_parse_url($u){return parse_url($u);} function esc_url_raw($u,$p=[]){return $u;} function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');} function get_queried_object(){return $GLOBALS['post'];} function is_post_publicly_viewable($p){return $p->post_status==='publish';} function wp_get_canonical_url($p){return 'https://example.test/real-post/';} function get_the_title($p){return 'Заголовок " <тест>'; } function get_post_thumbnail_id($p){return 11;} function get_query_var($k){return 0;} function home_url($p){return 'https://example.test'.$p;} function absint($n){return abs((int)$n);} function get_post_mime_type($id){return $GLOBALS['images'][$id]['type']??'';} function get_post($id){return new WP_Post();} function wp_get_attachment_image_src($id,$size){return $GLOBALS['images'][$id]['src']??false;} function get_post_meta($id,$key,$single){return 'Изображение "бренда"';}
require __DIR__.'/../../plugins/enatura-social-preview/enatura-social-preview.php';
function check($ok,$label){if(!$ok){throw new Exception($label);}echo "PASS $label\n";}
function output(){ob_start(); ENatura_Social_Preview::render(); return ob_get_clean();}
check(ENatura_Social_Preview::url('javascript:alert(1)')==='', 'reject unsafe URL');
check(ENatura_Social_Preview::url('/relative')==='', 'reject relative URL');
check(ENatura_Social_Preview::url('https://user:pass@example.test/')==='', 'reject credentials');
$d=ENatura_Social_Preview::data(); check($d['description']==='Описание записи' && $d['url']==='https://example.test/real-post/', 'post source and canonical');
$html=output(); check(strpos($html,'&quot;')!==false && strpos($html,'<тест>')===false,'attribute escaping'); check(strpos($html,'og:image')===false,'no invented image');
$GLOBALS['post']->post_password='secret'; check(output()==='', 'password protected even for authenticated visitor'); $GLOBALS['post']->post_password='';
$GLOBALS['post']->post_status='draft'; check(output()==='', 'draft excluded'); $GLOBALS['post']->post_status='publish';
foreach(['search','preview'] as $c){$GLOBALS['context']=$c;check(output()==='', $c.' excluded');}
$GLOBALS['context']='front'; $d=ENatura_Social_Preview::data();check($d['title']==='Реальный сайт' && $d['description']==='Описание сайта','front uses actual site settings');
$GLOBALS['context']='single'; $GLOBALS['opts']['blog_public']=0; check(output()==='', 'private site excluded');$GLOBALS['opts']['blog_public']=1;
$GLOBALS['images']=[11=>['type'=>'image/png','src'=>['https://example.test/small.png',250,250]],12=>['type'=>'image/jpeg','src'=>['https://example.test/brand.jpg',1200,630]]]; $GLOBALS['opts']['enatura_social_preview']=['image_id'=>12];
$d=ENatura_Social_Preview::data();check($d['image']['width']===1200,'brand replaces tiny thumbnail');check(strpos(output(),'summary_large_image')!==false,'large image card');
$GLOBALS['opts']['enatura_social_preview']['mode']='off';check(output()==='', 'manual disable');$GLOBALS['opts']['enatura_social_preview']['mode']='auto';
define('WPSEO_VERSION','test'); check(output()==='', 'SEO auto coexistence');
$GLOBALS['opts']['enatura_social_preview']['mode']='on';check(strpos(output(),'og:title')!==false,'explicit override');
$GLOBALS['post']->post_type='product';check(strpos(output(),'og:title')!==false,'product type without WooCommerce');
if (!function_exists('wc_get_product')) {
    function wc_get_product($id){return new class {function get_name(){return 'Настоящий товар';}function get_short_description(){return '<b>Описание товара</b>';}function get_description(){return 'Полное описание';}function get_image_id(){return 12;}};}
}
$d=ENatura_Social_Preview::data();check($d['title']==='Настоящий товар' && $d['description']==='Описание товара' && $d['image']['width']===1200,'WooCommerce getters');
check(strpos(output(),'price')===false && strpos(output(),'availability')===false,'no fabricated commercial fields');
check(ENatura_Social_Preview::clean('<script>alert(1)</script><p>Безопасно</p>')==='Безопасно','script body removed');
$GLOBALS['opts']['enatura_social_preview']['image_id']=0; $GLOBALS['post']->post_type='post';$d=ENatura_Social_Preview::data();check($d['image']['width']===250 && strpos(output(),'summary_large_image')===false,'small image uses summary');
$GLOBALS['images'][11]['src'][1]=100; check(ENatura_Social_Preview::data()['image']===null,'undersized image omitted');
