<?php
    // src/Controller/PurchaseController.php
    
    namespace App\Controller;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Doctrine\ORM\EntityManagerInterface;
    use App\Helper\Helper;

    use App\Entity\Product;
    use App\Entity\Coupon;

    require  __DIR__.'/../../PaymentProcessor/PaypalPaymentProcessor.php';
    use PaypalPaymentProcessor;
    require  __DIR__.'/../../PaymentProcessor/StripePaymentProcessor.php';
    use StripePaymentProcessor;

    class PurchaseController extends AbstractController
    {
        #[Route('/purchase', methods: ['POST'])]
        public function purchase(EntityManagerInterface $entityManager, Request $request): Response
        {
            $data = json_decode($request->getContent(), true);

            // Валидация данных
            if (!isset($data['product']) || !isset($data['taxNumber']) || !isset($data['paymentProcessor'])) {
                return new Response(json_encode(['error' => 'Invalid input data']), 400, ['Content-Type' => 'application/json']);
            }
            // получаем продкут из бд
            $product = $entityManager->getRepository(Product::class)->findOneBy(['id' => $data['product']]);
            if (!$product) {
                return new Response(json_encode(['status' => 'error', 'message' => 'No product found for id '.$data['product']]), 400, ['Content-Type' => 'application/json']);
            }
            // используем купон если есть
            if (isset($data['couponCode'])) {
                $coupon = $entityManager->getRepository(Coupon::class)->findOneBy(['name' => $data['couponCode']]);
                if (!$coupon) {
                    return new Response(json_encode(['status' => 'error', 'message' => 'No coupon found for '.$data['couponCode']]), 400, ['Content-Type' => 'application/json']);
                }
                $price = Helper::applyCoupon($product->getPrice(), $coupon->getType(), $coupon->getDiscount());
            }else{
                $price = $product->getPrice();
            }
            // добавляем налог
            try {
                $price = Helper::addTax($price, $data['taxNumber']);
            } catch (\Throwable $th) {
                return new Response(json_encode(['status' => 'error', 'message' => $th->getMessage()]), 400, ['Content-Type' => 'application/json']);
            }
            // оплата
            switch ($data['paymentProcessor']) {
                case 'paypal':
                    $payment = new PaypalPaymentProcessor();
                    try {
                        $payment->pay($price);
                    } catch (\Throwable $th) {
                        return new Response(json_encode(['status' => 'error', 'message' => $th->getMessage(), 'price' => $price]), 400, ['Content-Type' => 'application/json']);
                    }
                    
                    break;
                case 'stripe':
                    $payment = new StripePaymentProcessor();
                    $result = $payment->processPayment($price);
                    if (!$result) {
                        return new Response(json_encode(['status' => 'error', 'message' => 'price to low for buying in this payment', 'price' => $price]), 400, ['Content-Type' => 'application/json']);
                    }
                    break;
                default:
                    break;
            }
            
            return new Response(json_encode(['status' => 'Success', 'price' => $price]), 200, ['Content-Type' => 'application/json']);
        }
        
    }

