<?

/*
 * Класс для получение оптимальную скидку
 */

use \Bitrix\Highloadblock as BH;

class DiscountPercent {

    const HighLoadBlockName = "UsersDiscount";
    const IBlockProperty = "DISCOUNT";
    const HBlockUserIDProperty = "UF_USER_ID";
    const HBlockUserDiscountProperty = "UF_USER_DISCOUNT";

    private static $userDiscountPercent = -1;

    private static function IncludeModule() {

        if (!CModule::IncludeModule("iblock") || !CModule::IncludeModule("sale") || !CModule::IncludeModule("highloadblock")) {
            //ShowError("Модуль не установлен.");
            return false;
        }

        return true;

    }

    /*
     * Получить скидку по коду торговому предложению
     */
    public static function getByProductSkuID($ProductID) {

        $discount = 0;

        if (self::IncludeModule()) {

            $userDiscount = self::getUserDiscount();
            $productDiscount = self::getProductSKUDiscount($ProductID);

            $discount = $userDiscount + $productDiscount - ($userDiscount * $productDiscount) / 100.0;

        }

        return $discount;

    }

    /*
     * Получить скидку по коду цены
     */
    public static function getByPriceID($PriceID) {

        $discount = 0;

        if (self::IncludeModule()) {

            $arPrice = CPrice::GetByID($PriceID);

            if ($arPrice) {
                $discount = self::getByProductSkuID($arPrice["PRODUCT_ID"]);
            }

        }

        return $discount;

    }


    /*
     * Скидка для торгового предложения
     */
    private function getProductSKUDiscount($ProductID) {

        $productDiscount = 0;

        $Product = CCatalogSku::GetProductInfo($ProductID);

        if ($Product) {

            $dbProp = CIBlockElement::GetProperty(
                $Product["IBLOCK_ID"],
                $Product["ID"],
                array(),
                array(
                    "CODE" => self::IBlockProperty
                )
            );
            if($arProp = $dbProp->Fetch()) {
                $arProp["VALUE"] = (int) $arProp["VALUE"];
                if ($arProp["VALUE"] > 0 && $arProp["VALUE"] < 100)
                    $productDiscount = $arProp["VALUE"];
            }

        }

        return $productDiscount;

    }

    /*
     * Получить скидка для текущего пользователя
     */
    private function getUserDiscount() {

        //Если уже определен скидка для пользователя
        if (self::$userDiscountPercent < 0) {
            self::setUserDiscount();
        }

        return self::$userDiscountPercent;

    }


    /*
     * Установить скидка для текущего пользователя
     */
    private function setUserDiscount() {

        global $USER;
        if (!$USER->GetID()) {
            self::$userDiscountPercent = 0;
            return;
        }

        self::$userDiscountPercent = 0;

        /************************* Highload-блоки *************************/
        $dbHightLoadBlock = BH\HighloadBlockTable::getList(
            array(
                "filter" => array(
                    "NAME" => self::HighLoadBlockName
                )
            )
        );

        if ( !($arHightLoadBlock = $dbHightLoadBlock->fetch()) ){
            ShowError("\"Highload-блоки\" с именем \"".self::HighLoadBlockName."\" не найден.");
            return;
        }
        /************************* /Highload-блоки *************************/

        /************************* элемент Highload-блока *************************/
        $Entity = BH\HighloadBlockTable::compileEntity($arHightLoadBlock);

        $Query = new \Bitrix\Main\Entity\Query($Entity);

        $Query->setSelect(array(self::HBlockUserDiscountProperty));
        $Query->setFilter(array(self::HBlockUserIDProperty => $USER->GetID()));

        $arQueryResult = $Query->exec();

        $dbResult = new CDBResult($arQueryResult);
        if ($arUserDiscount = $dbResult->Fetch()){
            $arUserDiscount[self::HBlockUserDiscountProperty] = (int) $arUserDiscount[self::HBlockUserDiscountProperty];
            //Устанавливаем значение пользовательской скидки
            if ($arUserDiscount[self::HBlockUserDiscountProperty] > 0 && $arUserDiscount[self::HBlockUserDiscountProperty] < 100)
                self::$userDiscountPercent = $arUserDiscount[self::HBlockUserDiscountProperty];
        }
        /************************* /элемент Highload-блока *************************/

    }

}

