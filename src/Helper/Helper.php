<?php
    // src/helper/helper.php
    namespace App\Helper;

    class Helper{
        /**
         * Рассчитывает общую цену с учетом налога на основе кода страны.
         * Код страны извлекается из начала налогового номера.
         *
         * @param int $price Исходная цена без налога.
         * @param string $taxNumber Налоговый номер, содержащий код страны.
         * @return int Общая цена с учетом налога.
         */
        public static function addTax(int $price, string $taxNumber): int{
            // Извлечение кода страны из налогового номера
            $countryCode = substr($taxNumber, 0, 2);

            // Применение налоговой ставки на основе кода страны
            switch ($countryCode) {
                case 'DE': // Германия
                    return (int) round($price * 1.19);
                case 'IT':// Италия
                    return (int) round($price * 1.22);
                case 'GR':// Греция
                    return (int) round($price * 1.24); 
                case 'FR': // Франция
                    return (int) round($price * 1.20);
                default:
                    throw new \InvalidArgumentException('Unsupported country code');
            }
        }
        /**
         * высчитываем скидку при использовании купона
         *
         * @param int $price Исходная цена без налога.
         * @param string $type тип скидки фиксированная или процент.
         * @param int $discount размер скидки.
         * @return int Общая цена с учетом скидки.
         */
        public static function applyCoupon(int $price, string $type, int $discount): int{
            switch ($type) {
                case 'fixed':
                    $discountedAmount = $price - $discount;
                    break;
                case 'percent':
                    $discountedAmount = $price - ($price * $discount / 100);
                    break;
                default:
                    throw new \InvalidArgumentException('Неизвестный тип купона.');
            }

            return max(0, $discountedAmount);
        }
    }