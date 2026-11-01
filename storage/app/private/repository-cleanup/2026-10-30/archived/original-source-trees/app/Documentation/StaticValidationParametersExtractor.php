<?php

namespace App\Documentation;

use Dedoc\Scramble\Diagnostics\DiagnosticsCollector;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ValidateCallParametersExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\RulesToParameters;
use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\PrettyPrinter;

/** Documents dynamic validation without evaluating request/model-dependent PHP. */
class StaticValidationParametersExtractor extends ValidateCallParametersExtractor
{
    private const ACTIONS = [
        'App\\Http\\Controllers\\Api\\Chat\\PresenceController@update',
        'App\\Http\\Controllers\\TargetController@store',
        'App\\Modules\\Users\\Controllers\\HrSettingController@store',
        'App\\Modules\\Users\\Controllers\\HrSettingController@update',
        'App\\Modules\\Orders\\Controllers\\OrderReasonController@store',
        'App\\Modules\\Orders\\Controllers\\OrderReasonController@update',
        'App\\Modules\\Catalog\\Controllers\\CategoryController@update',
        'App\\Modules\\Catalog\\Controllers\\WarehouseController@update',
        'App\\Modules\\Catalog\\Controllers\\ProductAttributeController@update',
        'App\\Modules\\Orders\\Controllers\\OrderReturnController@store',
        'App\\Modules\\Orders\\Controllers\\OrderReturnController@processQc',
        'App\\Modules\\Core\\Controllers\\ShippingController@updateShipmentStatus',
    ];

    public function __construct(PrettyPrinter $printer, private TypeTransformer $transformer, DiagnosticsCollector $diagnostics)
    {
        parent::__construct($printer, $transformer, $diagnostics);
    }

    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        if (! in_array($routeInfo->className().'@'.$routeInfo->methodName(), self::ACTIONS, true)) {
            return parent::handle($routeInfo, $parameterExtractionResults);
        }

        $node = $routeInfo->actionNode();
        $finder = new NodeFinder;
        $call = $finder->findFirst($node, fn (Node $n) => $n instanceof Node\Expr\MethodCall
            && $n->name instanceof Node\Identifier && $n->name->name === 'validate');
        $rulesNode = $call?->args[0]->value;
        $rules = [];
        if ($rulesNode instanceof Node\Expr\Variable) {
            $assignment = $finder->findFirst($node, fn (Node $n) => $n instanceof Node\Expr\Assign
                && $n->var instanceof Node\Expr\Variable && $n->var->name === $rulesNode->name
                && $n->expr instanceof Node\Expr\Array_);
            $rulesArray = $assignment?->expr;
            // Conditional fields remain optional in the structural schema;
            // their period-specific requirements still run in the controller.
            foreach ($finder->find($node, fn (Node $n) => $n instanceof Node\Expr\Assign
                && $n->var instanceof Node\Expr\ArrayDimFetch
                && $n->var->var instanceof Node\Expr\Variable && $n->var->var->name === $rulesNode->name
                && $n->var->dim instanceof Node\Scalar\String_) as $addition) {
                $rules[$addition->var->dim->value] = array_values(array_diff($this->literalRules($addition->expr), ['required']));
            }
        } else {
            $rulesArray = $rulesNode;
        }
        if (! $rulesArray instanceof Node\Expr\Array_) {
            throw new \LogicException('Expected validation rules array for '.$routeInfo->className().'@'.$routeInfo->methodName());
        }
        foreach ($rulesArray->items as $item) {
            if ($item?->key instanceof Node\Scalar\String_) {
                $rules[$item->key->value] = $this->literalRules($item->value);
            }
        }
        foreach ($finder->find($node, fn (Node $n) => $n instanceof Node\Expr\MethodCall
            && $n->var instanceof Node\Expr\Variable && $n->var->name === 'request'
            && $n->name instanceof Node\Identifier && $n->name->name === 'boolean'
            && ($n->args[0]->value ?? null) instanceof Node\Scalar\String_) as $booleanRead) {
            $rules[$booleanRead->args[0]->value->value] ??= ['boolean'];
        }

        $parameters = (new RulesToParameters($rules, [], $this->transformer, 'body'))->mergeDotNotatedKeys(false)->handle();
        foreach ($parameters as $parameter) {
            if (in_array($parameter->name, ['target_month', 'target_year', 'start_date', 'end_date'], true)
                && $routeInfo->className() === 'App\\Http\\Controllers\\TargetController') {
                $parameter->description = match ($parameter->name) {
                    'target_month' => 'Required for monthly targets (1–12).',
                    'target_year' => 'Required for monthly and yearly targets.',
                    default => 'Required for daily targets; end_date must be on or after start_date.',
                };
            }
        }

        return [...$parameterExtractionResults, new ParametersExtractionResult($parameters)];
    }

    /** Keep structural literals; never execute closures, models or Rule objects. */
    private function literalRules(Node\Expr $node): array
    {
        if ($node instanceof Node\Expr\Array_) {
            return array_merge([], ...array_map(fn ($item) => $item ? $this->literalRules($item->value) : [], $node->items));
        }
        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            return $this->literalRules($node->left);
        }
        if ($node instanceof Node\Scalar\InterpolatedString && ($node->parts[0] ?? null) instanceof Node\InterpolatedStringPart) {
            return $this->literalRules(new Node\Scalar\String_($node->parts[0]->value));
        }
        if (! $node instanceof Node\Scalar\String_) {
            return [];
        }

        // Existence, uniqueness and contextual membership are enforced at
        // runtime; they do not describe OpenAPI property types or bounds.
        return array_values(array_filter(explode('|', $node->value),
            fn ($rule) => $rule !== '' && ! preg_match('/^(exists|unique)(:|$)/', $rule)));
    }
}
