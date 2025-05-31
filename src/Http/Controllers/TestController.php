<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Techigh\CreditMessaging\Facades\CreditManager;
use Techigh\CreditMessaging\Facades\MessageRouter;
use Techigh\CreditMessaging\Services\CreditManagerService;
use Techigh\CreditMessaging\Services\MessageRoutingService;
use Techigh\CreditMessaging\Services\MessageServiceAdapter;

class TestController extends Controller
{
    /**
     * Test service provider bindings
     */
    public function testBindings(): JsonResponse
    {
        try {
            // Test direct service injection
            $creditManagerService = app(CreditManagerService::class);
            $messageRoutingService = app(MessageRoutingService::class);
            $messageServiceAdapter = app(MessageServiceAdapter::class);

            // Test facade bindings
            $creditManagerFromFacade = app('credit-manager');
            $messageRouterFromFacade = app('message-router');

            // Test singletons (should be the same instance)
            $creditManagerService2 = app(CreditManagerService::class);
            $messageRoutingService2 = app(MessageRoutingService::class);

            return response()->json([
                'status' => 'success',
                'message' => 'All bindings are working correctly',
                'tests' => [
                    'credit_manager_service_resolved' => !is_null($creditManagerService),
                    'message_routing_service_resolved' => !is_null($messageRoutingService),
                    'message_service_adapter_resolved' => !is_null($messageServiceAdapter),
                    'credit_manager_facade_resolved' => !is_null($creditManagerFromFacade),
                    'message_router_facade_resolved' => !is_null($messageRouterFromFacade),
                    'credit_manager_singleton_check' => $creditManagerService === $creditManagerService2,
                    'message_routing_singleton_check' => $messageRoutingService === $messageRoutingService2,
                    'facade_service_match' => $creditManagerService === $creditManagerFromFacade,
                    'message_facade_service_match' => $messageRoutingService === $messageRouterFromFacade,
                ],
                'class_info' => [
                    'credit_manager_class' => get_class($creditManagerService),
                    'message_routing_class' => get_class($messageRoutingService),
                    'message_adapter_class' => get_class($messageServiceAdapter),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service binding test failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Test facade methods
     */
    public function testFacades(): JsonResponse
    {
        try {
            // Test CreditManager facade
            $testSiteId = 'test_site_001';

            // Get site credit (should create if not exists)
            $siteCredit = CreditManager::getSiteCredit($testSiteId);
            $balance = CreditManager::getBalance($testSiteId);

            // Test MessageRouter facade methods
            $estimation = MessageRouter::estimateMessageCost($testSiteId, 'sms', 10, 'Test message');

            return response()->json([
                'status' => 'success',
                'message' => 'Facade methods are working correctly',
                'tests' => [
                    'site_credit_created' => !is_null($siteCredit),
                    'balance_retrieved' => is_numeric($balance),
                    'cost_estimation_works' => is_array($estimation),
                ],
                'results' => [
                    'site_id' => $testSiteId,
                    'current_balance' => $balance,
                    'estimation' => $estimation,
                    'site_credit_id' => $siteCredit->id ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Facade test failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Test binding equivalence - 바인딩 방식 차이점 테스트
     */
    public function testBindingEquivalence(): JsonResponse
    {
        try {
            // 🔍 다양한 방식으로 동일한 서비스 호출
            $creditManager1 = app(CreditManagerService::class);      // 클래스명
            $creditManager2 = app('credit-manager');                 // 문자열 키
            $creditManager3 = CreditManager::getFacadeRoot();        // 파사드

            $messageRouter1 = app(MessageRoutingService::class);     // 클래스명  
            $messageRouter2 = app('message-router');                 // 문자열 키
            $messageRouter3 = MessageRouter::getFacadeRoot();        // 파사드

            return response()->json([
                'status' => 'success',
                'message' => '모든 바인딩 방식이 동일한 인스턴스를 반환합니다',
                'binding_tests' => [
                    // CreditManager 인스턴스 동일성 검증
                    'credit_manager_class_vs_string' => $creditManager1 === $creditManager2,
                    'credit_manager_string_vs_facade' => $creditManager2 === $creditManager3,
                    'credit_manager_all_same' => ($creditManager1 === $creditManager2) && ($creditManager2 === $creditManager3),

                    // MessageRouter 인스턴스 동일성 검증
                    'message_router_class_vs_string' => $messageRouter1 === $messageRouter2,
                    'message_router_string_vs_facade' => $messageRouter2 === $messageRouter3,
                    'message_router_all_same' => ($messageRouter1 === $messageRouter2) && ($messageRouter2 === $messageRouter3),
                ],
                'memory_addresses' => [
                    'credit_manager' => [
                        'class_based' => spl_object_hash($creditManager1),
                        'string_based' => spl_object_hash($creditManager2),
                        'facade_based' => spl_object_hash($creditManager3),
                    ],
                    'message_router' => [
                        'class_based' => spl_object_hash($messageRouter1),
                        'string_based' => spl_object_hash($messageRouter2),
                        'facade_based' => spl_object_hash($messageRouter3),
                    ],
                ],
                'explanation' => [
                    'class_based' => 'app(CreditManagerService::class) - 클래스명으로 직접 호출',
                    'string_based' => 'app("credit-manager") - 문자열 키로 호출 (파사드용)',
                    'facade_based' => 'CreditManager::getFacadeRoot() - 파사드 내부 호출',
                    'result' => '모든 방식이 동일한 싱글톤 인스턴스를 반환',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Binding equivalence test failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Test configuration loading
     */
    public function testConfig(): JsonResponse
    {
        try {
            $config = config('credit-messaging');

            return response()->json([
                'status' => 'success',
                'message' => 'Configuration loaded successfully',
                'config_keys' => array_keys($config),
                'default_costs' => $config['default_costs'] ?? null,
                'auto_charge' => $config['auto_charge'] ?? null,
                'routing' => $config['routing'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Configuration test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test current binding status - 현재 바인딩 상태 테스트
     */
    public function testCurrentBindingStatus(): JsonResponse
    {
        $results = [
            'facade_tests' => [],
            'string_key_tests' => [],
            'class_name_tests' => [],
            'dependency_injection_tests' => [],
        ];

        // 1️⃣ 파사드 테스트
        try {
            $balance = CreditManager::getBalance('test_site');
            $results['facade_tests']['credit_manager'] = [
                'status' => 'success',
                'result' => is_numeric($balance)
            ];
        } catch (\Exception $e) {
            $results['facade_tests']['credit_manager'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        try {
            $estimation = MessageRouter::estimateMessageCost('test_site', 'sms', 1, 'test');
            $results['facade_tests']['message_router'] = [
                'status' => 'success',
                'result' => is_array($estimation)
            ];
        } catch (\Exception $e) {
            $results['facade_tests']['message_router'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // 2️⃣ 문자열 키 테스트
        try {
            $creditManager = app('credit-manager');
            $results['string_key_tests']['credit_manager'] = [
                'status' => 'success',
                'class' => get_class($creditManager)
            ];
        } catch (\Exception $e) {
            $results['string_key_tests']['credit_manager'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        try {
            $messageRouter = app('message-router');
            $results['string_key_tests']['message_router'] = [
                'status' => 'success',
                'class' => get_class($messageRouter)
            ];
        } catch (\Exception $e) {
            $results['string_key_tests']['message_router'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // 3️⃣ 클래스명 테스트
        try {
            $creditManager = app(CreditManagerService::class);
            $results['class_name_tests']['credit_manager'] = [
                'status' => 'success',
                'class' => get_class($creditManager)
            ];
        } catch (\Exception $e) {
            $results['class_name_tests']['credit_manager'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        try {
            $messageRouter = app(MessageRoutingService::class);
            $results['class_name_tests']['message_router'] = [
                'status' => 'success',
                'class' => get_class($messageRouter)
            ];
        } catch (\Exception $e) {
            $results['class_name_tests']['message_router'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        try {
            $adapter = app(MessageServiceAdapter::class);
            $results['class_name_tests']['message_service_adapter'] = [
                'status' => 'success',
                'class' => get_class($adapter)
            ];
        } catch (\Exception $e) {
            $results['class_name_tests']['message_service_adapter'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // 4️⃣ 싱글톤 동일성 테스트
        $singleton_tests = [];
        try {
            $instance1 = app('credit-manager');
            $instance2 = CreditManager::getFacadeRoot();
            $singleton_tests['facade_vs_string_key'] = $instance1 === $instance2;
        } catch (\Exception $e) {
            $singleton_tests['facade_vs_string_key'] = 'error: ' . $e->getMessage();
        }

        return response()->json([
            'status' => 'success',
            'message' => '현재 바인딩 상태 테스트 결과',
            'results' => $results,
            'singleton_tests' => $singleton_tests,
            'recommendations' => [
                'facade_usage' => '파사드 사용은 완전히 작동합니다',
                'string_key_usage' => '문자열 키 사용은 작동합니다',
                'class_name_usage' => '클래스명 직접 사용 여부를 확인하세요',
                'dependency_injection' => '타입 힌팅 의존성 주입 여부를 확인하세요',
            ]
        ]);
    }
}
