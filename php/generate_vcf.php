<?php
include('includes/config.php');

$eid = isset($_GET['eid']) ? (int)$_GET['eid'] : 0;
if(!$eid){ http_response_code(400); exit('Missing employee ID'); }

$emp = $conn->query("SELECT e.*, c.company_name FROM employees e JOIN companies c ON c.id=e.company_id WHERE e.id=$eid")->fetch_assoc();
if(!$emp){ http_response_code(404); exit('Not found'); }

$card = $emp['card_data'] ? json_decode($emp['card_data'], true) : [];
$phone   = $card['contact']['phone']   ?? '';
$email   = $card['contact']['email']   ?? '';
$org     = $emp['company_name'];
$name    = $emp['emp_name'];

$vcf  = "BEGIN:VCARD\r\n";
$vcf .= "VERSION:3.0\r\n";
$vcf .= "FN:".$name."\r\n";
$vcf .= "ORG:".$org."\r\n";
if(!empty($emp['designation'])) $vcf .= "TITLE:".$emp['designation']."\r\n";
if($phone) $vcf .= "TEL;TYPE=CELL:".$phone."\r\n";
if($email) $vcf .= "EMAIL;TYPE=INTERNET:".$email."\r\n";
$vcf .= "END:VCARD\r\n";

header('Content-Type: text/vcard; charset=utf-8');
header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9]+/','-', $name).'.vcf"');
echo $vcf;
