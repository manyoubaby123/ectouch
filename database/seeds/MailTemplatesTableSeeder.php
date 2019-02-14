<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MailTemplatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        DB::table('mail_templates')->delete();
        
        DB::table('mail_templates')->insert(array(

            array(
                'template_id' => 1,
                'template_code' => 'send_password',
                'is_html' => 1,
                'template_subject' => '密码找回',
                'template_content' => '{$user_name}您好！<br>
<br>
您已经进行了密码重置的操作，请点击以下链接(或者复制到您的浏览器):<br>
<br>
<a href="{$reset_email}" target="_blank">{$reset_email}</a><br>
<br>
以确认您的新密码重置操作！<br>
<br>
{$shop_name}<br>
{$send_date}',
                'last_modify' => 1194824789,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 2,
                'template_code' => 'order_confirm',
                'is_html' => 0,
                'template_subject' => '订单确认通知',
                'template_content' => '亲爱的{$order.consignee}，你好！ 

我们已经收到您于 {$order.formated_add_time} 提交的订单，该订单编号为：{$order.order_sn} 请记住这个编号以便日后的查询。

{$shop_name}
{$sent_date}


',
                'last_modify' => 1158226370,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 3,
                'template_code' => 'deliver_notice',
                'is_html' => 1,
                'template_subject' => '发货通知',
                'template_content' => '亲爱的{$order.consignee}。你好！</br></br>

您的订单{$order.order_sn}已于{$send_time}按照您预定的配送方式给您发货了。</br>
</br>
{if $order.invoice_no}发货单号是{$order.invoice_no}。</br>{/if}
</br>
在您收到货物之后请点击下面的链接确认您已经收到货物：</br>
<a href="{$confirm_url}" target="_blank">{$confirm_url}</a></br></br>
如果您还没有收到货物可以点击以下链接给我们留言：</br></br>
<a href="{$send_msg_url}" target="_blank">{$send_msg_url}</a></br>
<br>
再次感谢您对我们的支持。欢迎您的再次光临。 <br>
<br>
{$shop_name} </br>
{$send_date}',
                'last_modify' => 1194823291,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 4,
                'template_code' => 'order_cancel',
                'is_html' => 0,
                'template_subject' => '订单取消',
                'template_content' => '亲爱的{$order.consignee}，你好！ 

您的编号为：{$order.order_sn}的订单已取消。

{$shop_name}
{$send_date}',
                'last_modify' => 1156491130,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 5,
                'template_code' => 'order_invalid',
                'is_html' => 0,
                'template_subject' => '订单无效',
                'template_content' => '亲爱的{$order.consignee}，你好！

您的编号为：{$order.order_sn}的订单无效。

{$shop_name}
{$send_date}',
                'last_modify' => 1156491164,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 6,
                'template_code' => 'send_bonus',
                'is_html' => 0,
                'template_subject' => '发红包',
                'template_content' => '亲爱的{$user_name}您好！

恭喜您获得了{$count}个红包，金额{if $count > 1}分别{/if}为{$money}

{$shop_name}
{$send_date}
',
                'last_modify' => 1156491184,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 7,
                'template_code' => 'group_buy',
                'is_html' => 1,
                'template_subject' => '团购商品',
                'template_content' => '亲爱的{$consignee}，您好！<br>
<br>
您于{$order_time}在本店参加团购商品活动，所购买的商品名称为：{$goods_name}，数量：{$goods_number}，订单号为：{$order_sn}，订单金额为：{$order_amount}<br>
<br>
此团购商品现在已到结束日期，并达到最低价格，您现在可以对该订单付款。<br>
<br>
请点击下面的链接：<br>
<a href="{$shop_url}" target="_blank">{$shop_url}</a><br>
<br>
请尽快登录到用户中心，查看您的订单详情信息。 <br>
<br>
{$shop_name} <br>
<br>
{$send_date}',
                'last_modify' => 1194824668,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 8,
                'template_code' => 'register_validate',
                'is_html' => 1,
                'template_subject' => '邮件验证',
                'template_content' => '{$user_name}您好！<br><br>

这封邮件是 {$shop_name} 发送的。你收到这封邮件是为了验证你注册邮件地址是否有效。如果您已经通过验证了，请忽略这封邮件。<br>
请点击以下链接(或者复制到您的浏览器)来验证你的邮件地址:<br>
<a href="{$validate_email}" target="_blank">{$validate_email}</a><br><br>

{$shop_name}<br>
{$send_date}',
                'last_modify' => 1162201031,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 9,
                'template_code' => 'virtual_card',
                'is_html' => 0,
                'template_subject' => '虚拟卡片',
                'template_content' => '亲爱的{$order.consignee}
你好！您的订单{$order.order_sn}中{$goods.goods_name} 商品的详细信息如下:
{foreach from=$virtual_card item=card}
{if $card.card_sn}卡号：{$card.card_sn}{/if}{if $card.card_password}卡片密码：{$card.card_password}{/if}{if $card.end_date}截至日期：{$card.end_date}{/if}
{/foreach}
再次感谢您对我们的支持。欢迎您的再次光临。

{$shop_name} 
{$send_date}',
                'last_modify' => 1162201031,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 10,
                'template_code' => 'attention_list',
                'is_html' => 0,
                'template_subject' => '关注商品',
                'template_content' => '亲爱的{$user_name}您好~

您关注的商品 : {$goods_name} 最近已经更新,请您查看最新的商品信息

{$goods_url}

{$shop_name} 
{$send_date}',
                'last_modify' => 1183851073,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 11,
                'template_code' => 'remind_of_new_order',
                'is_html' => 0,
                'template_subject' => '新订单通知',
                'template_content' => '亲爱的店长，您好：
快来看看吧，又有新订单了。
订单号:{$order.order_sn} 
订单金额:{$order.order_amount}，
用户购买商品:{foreach from=$goods_list item=goods_data}{$goods_data.goods_name}(货号:{$goods_data.goods_sn})    {/foreach} 

收货人:{$order.consignee}， 
收货人地址:{$order.address}，
收货人电话:{$order.tel} {$order.mobile}, 
配送方式:{$order.shipping_name}(费用:{$order.shipping_fee}), 
付款方式:{$order.pay_name}(费用:{$order.pay_fee})。

系统提醒
{$send_date}',
                'last_modify' => 1196239170,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 12,
                'template_code' => 'goods_booking',
                'is_html' => 1,
                'template_subject' => '缺货回复',
                'template_content' => '亲爱的{$user_name}。你好！</br></br>{$dispose_note}</br></br>您提交的缺货商品链接为</br></br><a href="{$goods_link}" target="_blank">{$goods_name}</a></br><br>{$shop_name} </br>{$send_date}',
                'last_modify' => 0,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 13,
                'template_code' => 'user_message',
                'is_html' => 1,
                'template_subject' => '留言回复',
                'template_content' => '亲爱的{$user_name}。你好！</br></br>对您的留言：</br>{$message_content}</br></br>店主作了如下回复：</br>{$message_note}</br></br>您可以随时回到店中和店主继续沟通。</br>{$shop_name}</br>{$send_date}',
                'last_modify' => 0,
                'last_send' => 0,
                'type' => 'template',
            ),

            array(
                'template_id' => 14,
                'template_code' => 'recomment',
                'is_html' => 1,
                'template_subject' => '用户评论回复',
                'template_content' => '亲爱的{$user_name}。你好！</br></br>对您的评论：</br>“{$comment}”</br></br>店主作了如下回复：</br>“{$recomment}”</br></br>您可以随时回到店中和店主继续沟通。</br>{$shop_name}</br>{$send_date}',
                'last_modify' => 0,
                'last_send' => 0,
                'type' => 'template',
            ),
        ));
    }
}
