<?php
// No database access: exercise shared validation boundaries with form and JSON input shapes.
$storefront=in_array('--storefront',$argv,true);
require dirname(__DIR__,2).($storefront?'/storefront/bootstrap/autoload.php':'/vendor/autoload.php');
$app=require dirname(__DIR__,2).($storefront?'/storefront/bootstrap/app.php':'/bootstrap/app.php');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$checks=0;
function verify($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
verify(Validator::make([],[]) instanceof App\Validation\TypedValidator,'shared typed validator must be registered');
$data=['quantity'=>'2','warehouse_id'=>'17','nullable_id'=>null,'empty'=>'','optional'=>null,'items'=>[['product_id'=>'12','quantity'=>'1.25'],['product_id'=>14,'quantity'=>2.5]],'ids'=>['1','2',3], 'amount'=>'2000.01','phone'=>'0999999999','pincode'=>'012345','code'=>'001234','sku'=>'000123','enabled'=>'0'];
$rules=['quantity'=>'required|integer|min:1|max:1000','warehouse_id'=>['required','integer'],'nullable_id'=>'nullable|integer','empty'=>'sometimes|integer','optional'=>'nullable|integer','missing'=>'sometimes|integer','items'=>'array','items.*.product_id'=>'integer','items.*.quantity'=>'numeric|min:0.01','ids'=>'array','ids.*'=>'integer','amount'=>'numeric','phone'=>'string','pincode'=>'digits:6','code'=>'digits:6','sku'=>'string','enabled'=>'boolean'];
$validator=Validator::make($data,$rules);$result=$validator->validate();
verify($result['quantity']===2 && $result['warehouse_id']===17,'integer fields must be typed');
verify($result['nullable_id']===null && $result['empty']==='' && !array_key_exists('missing',$result),'nullable/empty/missing behavior must be retained');
verify($result['items'][0]['product_id']===12 && $result['ids']===[1,2,3],'nested and wildcard integers must be typed');
verify($result['items'][0]['quantity']==='1.25' && $result['items'][1]['quantity']===2.5 && $result['amount']==='2000.01','decimal quantities and money must not become integers');
verify($result['phone']==='0999999999' && $result['pincode']==='012345' && $result['code']==='001234' && $result['sku']==='000123','leading-zero text identifiers must remain unchanged');
verify($result['enabled']==='0','boolean rules must retain their previous representation');
verify($validator->safe()->only(['quantity'])===['quantity'=>2] && $validator->validated()['ids']===[1,2,3],'safe/validated access must agree');
verify($validator->getData()['quantity']==='2','raw request/validation input must not be rewritten');
$literal=Validator::make(['literal.key'=>'8','groups'=>[['line.id'=>'9']]],['literal\\.key'=>'integer','groups.*.line\\.id'=>'integer'])->validate();
verify($literal['literal.key']===8 && $literal['groups'][0]['line.id']===9,'escaped literal dots must remain literal keys');
foreach(['2.5','not-a-number','1001',[],null] as $bad){try{Validator::make(['quantity'=>$bad],['quantity'=>'required|integer|min:1|max:1000'])->validate();throw new RuntimeException('invalid quantity accepted');}catch(ValidationException $e){verify(isset($e->errors()['quantity']),'invalid values must still fail validation');}}
$excluded=Validator::make(['skip'=>'yes','count'=>'3'],['skip'=>'string','count'=>'exclude_if:skip,yes|integer'])->validate();verify(!array_key_exists('count',$excluded),'excluded fields must stay excluded');
$after=false;$v=Validator::make(['count'=>'2'],['count'=>'integer']);$v->after(function()use(&$after){$after=true;});verify($v->validate()['count']===2 && $after,'existing validation callbacks must still execute');
$request=Illuminate\Http\Request::create('/test','POST',['count'=>'2','amount'=>'2.75']);verify($request->validate(['count'=>'integer','amount'=>'numeric'])===['count'=>2,'amount'=>'2.75'],'Request::validate must use typed data');
$form=new class extends Illuminate\Foundation\Http\FormRequest{public function rules():array{return ['ids.*'=>'integer','items.*.quantity'=>'numeric'];}public function authorize():bool{return true;}};
$form->setContainer($app);$form->setRedirector($app->make('redirect'));$form->replace(['ids'=>['1','2'],'items'=>[['quantity'=>'1.25']]]);$form->validateResolved();verify($form->validated('ids')===[1,2] && $form->validated('items.0.quantity')==='1.25','FormRequest fields must retain their declared numeric semantics');
echo 'PASS: '.$checks.' shared validation checks in '.($storefront?'storefront':'admin').".\n";
