<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Sales;

use Exception;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class Invoice Test
 */
class InvoiceTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/customer_invoice_with_two_products_and_custom_options.php
     */
    public function testOrdersQuery()
    {
        $query =
            <<<QUERY
query {
  customer
  {
  orders {
    items {
      order_number
      grand_total
      status
      invoices {
          id
          items{
            product_name
            product_sku
            product_sale_price {
              value
            }
            quantity_invoiced
          }
          total {
            subtotal {
              value
            }
            grand_total {
              value
            }
            total_shipping {
              value
            }
      			shipping_handling {
              total_amount {
                value
              }
              amount_exc_tax {
                value
              }
            }
          }
        }
    }
  }
 }
}
QUERY;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));

        $expectedData = [
            [
                'order_number' => '100000001',
                'status' => 'Processing',
                'grand_total' => 100.00
            ]
        ];

        $actualData = $response['customer']['orders']['items'];

        foreach ($expectedData as $key => $data) {
            $this->assertEquals(
                $data['order_number'],
                $actualData[$key]['order_number'],
                "order_number is different than the expected for order - " . $data['order_number']
            );
            $this->assertEquals(
                $data['grand_total'],
                $actualData[$key]['grand_total'],
                "grand_total is different than the expected for order - " . $data['order_number']
            );
            $this->assertEquals(
                $data['status'],
                $actualData[$key]['status'],
                "status is different than the expected for order - " . $data['order_number']
            );
        }
    }

    /**
     */
    public function testOrdersQueryNotAuthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customerOrders {
    items {
      increment_id
      grand_total
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * @param string $email
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}
