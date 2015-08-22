<?php
/**
 * Shop script plugin, easy pricelist.
 */
class shopEpricelistPluginFrontendPricelistAction extends shopFrontendAction
{
    protected function getPricelist()
    {
        $category_model = new shopCategoryModel;
        $categories = $category_model->getFullTree('id, depth, full_url, url, name', true);
        
        $url = wa()->getRouteUrl('shop/frontend/category', array('category_url' => '%CATEGORY_URL%'));
        $url_type = waRequest::param('url_type');

        foreach ($categories as $category) {
            $categories[$category['id']]['frontend_url'] = str_replace(
                '%CATEGORY_URL%', 
                $url_type == 1 ? $category['url'] : $category['full_url'], 
                $url
            );
            $categories[$category['id']]['name'] = htmlspecialchars($category['name']);
            $products_model = new shopProductsCollection('category/'.$category['id']);
            $products_model->filters(array('status' => 1));
            $categories[$category['id']]['products'] = $products_model->getProducts(
                'id, name, frontend_url, price, compare_price, currency, count', 
                0, 
                1000, 
                true
            );
        }

        return $categories;
    }

    
    public function execute()
    {
        // Use cache for pricelist array
        $app = wa()->getApp();
        $settings = new waAppSettingsModel;

        if ($cache_time = $settings->get(array($app, 'epricelist'), 'cache_time')) 
        {   
            $cache = new waSerializeCache('epricelist', $cache_time, $app);
            if ($cache->isCached()) {
                $categories = $cache->get();
            }
            if (empty($categories)) {
                $categories = $this->getPricelist();
                $cache->set($categories);
            }
        } else {
            $settings->set(array('shop', 'epricelist'), 'cache_time', 600);
            $categories = $this->getPricelist();
        }

        $this->getResponse()->setTitle(_wp('Pricelist'));
        
        // Set categories with products & template
        $this->view->assign('pricelist', $categories);
        
        if (file_exists($this->getTheme()->path.'/pricelist.html')) {
            $this->setThemeTemplate('pricelist.html');
        }
    }
}
