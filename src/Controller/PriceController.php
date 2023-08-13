<?php 
    // src/Controller/PriceController.php
    namespace App\Controller;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Doctrine\ORM\EntityManagerInterface;
    use app\Helper\Helper;

    use App\Entity\Product;
    use App\Entity\Coupon;

    class PriceController extends AbstractController{
        #[Route('/calculate', methods: ['POST'])]
        public function calculatePrice(EntityManagerInterface $entityManager, Request $request): Response{
            $data = json_decode($request->getContent(), true);
            
            // Валидация данных
            if (!isset($data['product']) || !isset($data['taxNumber'])) {
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
            
            return new Response(json_encode(['status' => 'Success', 'price' => $price]), 200, ['Content-Type' => 'application/json']);
        }
        
    }
