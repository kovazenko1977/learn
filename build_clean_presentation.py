import json

products_data = [
    {
        "id": 1, "slide": 5, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Vermouth Crystal Premium Ruby", "title_ru": "Вермут Кристалл Премиум Рубиновый",
        "sub_en": "Fortified Strong Flavored Wine", "sub_ru": "Вино улучшенного качества ароматизированное «Вермут Кристалл Премиум Рубиновый»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/vermout_rubin.png",
        "desc_en": "Produced by alcoholic fermentation of sweetened apple, chokeberry, and redcurrant juices to at least 10% ABV, followed by fortification and infusion with natural herbal extract 'Vermouth-Kryshtal' containing wormwood, yarrow, balsam tansy, peppermint, calamus, cardamom, thyme, oregano, coriander, nutmeg, and cinnamon.",
        "desc_ru": "Вино изготовлено путем спиртового брожения подсахаренных соков из яблок, черноплодной рябины, красной смородины до накопления объемной доли этилового спирта не менее 10% с последующим добавлением сахара, спирта и основы «Вермут-Крышталь», содержащую травы полыни горькой, тысячелистника, пижмы, мяты, аира, кардамона, чабреца, душицы, кориандра, мускатного ореха и корицы.",
        "abv": "18.0%", "sugar": "60 g/dm³", "shelf": "18 months", "pack_en": "Glass bottle 0.5L / 0.7L", "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "color_en": "From amber to red", "color_ru": "От янтарного до красного",
        "taste_en": "Harmonious, full, with notes of spicy-aromatic herbs", "taste_ru": "Гармоничный, полный, с тонами пряно-ароматических трав",
        "raw_en": "Natural apple, chokeberry and redcurrant juices, herbal extract", "raw_ru": "Натуральные яблочный, черноплоднорябиновый и красносмородиновый соки, травяной экстракт"
    },
    {
        "id": 2, "slide": 6, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Vermouth Crystal Premium Blueberry", "title_ru": "Вермут Кристалл Премиум Черничный",
        "sub_en": "Fortified Strong Flavored Wine", "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества ароматизированное «Вермут Кристалл Премиум Черничный»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/vermout_chernichny.png",
        "desc_en": "Crafted via alcoholic fermentation of natural apple and blueberry juices to at least 10% ABV, supplemented with sugar, high-purity ethyl alcohol, and natural herbal infusion 'Vermouth-Kryshtal' featuring bitter wormwood, yarrow, peppermint, calamus, cardamom, thyme, oregano, coriander, and cinnamon.",
        "desc_ru": "Вино изготовлено путем спиртового брожения соков из яблок и черники до накопления объемной доли этилового спирта не менее 10% с последующим добавлением сахара, спирта и основы «Вермут-Крышталь», содержащую полынь горькую, тысячелистник, мяту, аир, кардамон, чабрец, душицу, кориандр и корицу.",
        "abv": "18.0%", "sugar": "65 g/dm³", "shelf": "18 months", "pack_en": "Glass bottle 0.5L / 0.7L", "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "color_en": "From golden to amber with pink/red shades", "color_ru": "От золотистого до янтарного с оттенками от розового до красного",
        "taste_en": "Harmonious, full with apple aroma, herbal notes and wormwood bitterness", "taste_ru": "Гармоничный, полный с ароматом яблок, с тонами пряно-ароматических трав и полынной горчинкой",
        "raw_en": "Natural apple and blueberry juices, herbal extract", "raw_ru": "Натуральные яблочный и черничный соки, травяной экстракт"
    },
    {
        "id": 3, "slide": 7, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Sudarushka", "title_ru": "Сударушка",
        "sub_en": "Fortified Strong Fruit Wine", "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества «Сударушка»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/sudaruska.jpg",
        "desc_en": "Classic Belarusian fortified fruit wine produced from selected natural apple, chokeberry, and cranberry juices. Characterized by a rich fruity bouquet and traditional recipe.",
        "desc_ru": "Вино плодовое крепленое крепкое улучшенного качества «Сударушка». Изготовлено путем спиртового брожения подсахаренных соков из яблок, черноплодной рябины и клюквы.",
        "abv": "18.0%", "sugar": "60 g/dm³", "shelf": "18 months", "pack_en": "Glass bottle 0.5L / 0.7L", "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "color_en": "From light red to dark amber", "color_ru": "От светло-красного до темно-янтарного",
        "taste_en": "Harmonious, apple-based with chokeberry and cranberry notes", "taste_ru": "Гармоничный, яблочный с тонами черноплодной рябины и клюквы",
        "raw_en": "Natural apple, chokeberry and cranberry juices", "raw_ru": "Натуральные соки яблок, черноплодной рябины и клюквы"
    },
    {
        "id": 4, "slide": 8, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Berry Nocturne", "title_ru": "Ягодный ноктюрн",
        "sub_en": "Natural Table Semi-Sweet Fruit Wine", "sub_ru": "Вино фруктово-ягодное натуральное столовое полусладкое «Ягодный ноктюрн»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/nocturne.png",
        "desc_en": "Light natural semi-sweet wine produced by blending processed natural apple table wine base with natural extracts of dried elderberry, chokeberry, blackcurrant, and sugar.",
        "desc_ru": "Вино изготовлено путем купажирования яблочного натурального столового обработанного виноматериала, экстракта сухих ягод бузины, аронии, черной смородины и сахаросодержащих веществ.",
        "abv": "9.0% - 12.9%", "sugar": "50 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.7L", "pack_ru": "Стеклянная бутылка 0,7л",
        "color_en": "From light pink to red", "color_ru": "От светло-розового до красного",
        "taste_en": "Harmonious, light, with apple and berry bouquet", "taste_ru": "Гармоничный, легкий, с яблочно-ягодными тонами",
        "raw_en": "Apple wine base, extracts of elderberry, chokeberry, blackcurrant", "raw_ru": "Яблочный виноматериал, экстракты ягод бузины, аронии, черной смородины"
    },
    {
        "id": 5, "slide": 9, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Kirmash", "title_ru": "Кiрмаш",
        "sub_en": "Award-Winning Fortified Fruit Wine", "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества «Кiрмаш»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/kirmash15071.png",
        "desc_en": "Silver Medal Winner at the International Tasting Competition 'Wines 2012' in Minsk. Produced by alcoholic fermentation of sweetened juices from fresh apples and blackcurrants followed by fortification.",
        "desc_ru": "Вино отмечено серебряной медалью на Международном конкурсе-дегустации «Вина 2012» в г. Минске. Изготовлено путем спиртового брожения подсахаренных соков из яблок и черной смородины.",
        "abv": "18.0%", "sugar": "70 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.5L / 0.7L", "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "color_en": "From straw-yellow to dark amber, pinkish tint allowed", "color_ru": "От соломенного до темно-янтарного, допускается розовый оттенок",
        "taste_en": "Harmonious, apple with distinct blackcurrant tones", "taste_ru": "Гармоничный, яблочный с черносмородиновыми нотами",
        "raw_en": "Sweetened natural apple and blackcurrant juices", "raw_ru": "Подсахаренные натуральные соки яблок и черной смородины"
    },
    {
        "id": 6, "slide": 10, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Black Lady", "title_ru": "Черная дама",
        "sub_en": "Fortified Sweet Fruit Wine", "sub_ru": "Вино плодовое крепленое крепкое сладкое «Черная дама»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chernay_dama-298x1024.jpg",
        "desc_en": "Rich, sweet fortified fruit wine combining ripe apple undertones with dense blackcurrant notes, delivering a velvety texture and deep ruby color.",
        "desc_ru": "Вино плодовое крепленое крепкое сладкое «Черная дама». Изготовлено путем спиртового брожения подсахаренных соков из яблок и черной смородины с высокой концентрацией сахара.",
        "abv": "16.0%", "sugar": "160 g/dm³", "shelf": "18 months", "pack_en": "Glass bottle 0.5L / 0.7L", "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "color_en": "From light red to dark red", "color_ru": "От светло-красного до темно-красного",
        "taste_en": "Harmonious, full with rich apple and blackcurrant aroma", "taste_ru": "Гармоничный, полный с ароматом яблок и черной смородины",
        "raw_en": "Natural apple and blackcurrant juices", "raw_ru": "Натуральные соки яблок и черной смородины"
    },
    {
        "id": 7, "slide": 11, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Antal", "title_ru": "Анталь",
        "sub_en": "Fortified Strong Fruit Wine", "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества «Анталь»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/antal91.png",
        "desc_en": "In production since November 2011, Antal quickly earned consumer acclaim. Crafted by fermentation of sweetened apple and blackcurrant juices, followed by fortification with high-purity spirit.",
        "desc_ru": "Вино выпускается с ноября 2011г. Удачно вписалось в ассортимент вин комбината, пользуется постоянным спросом. Изготовлено путем спиртового брожения соков яблок и черной смородины.",
        "abv": "18.0%", "sugar": "50 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.5L / 0.7L", "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "color_en": "From straw-yellow to dark amber, pinkish tint allowed", "color_ru": "От соломенного до темно-янтарного, допускается розовый оттенок",
        "taste_en": "Harmonious, clean apple flavor with blackcurrant accents", "taste_ru": "Гармоничный, яблочный с черносмородиновыми оттенками",
        "raw_en": "Natural apple and blackcurrant juices", "raw_ru": "Натуральные соки яблок и черной смородины"
    },
    {
        "id": 8, "slide": 12, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Backouskaye", "title_ru": "Бацькоускае",
        "sub_en": "Natural Table Semi-Sweet Fruit Wine", "sub_ru": "Вино фруктово-ягодное натуральное столовое полусладкое «Бацькоускае»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/batkovskoe.png",
        "desc_en": "Traditional Belarusian semi-sweet wine produced by blending processed natural apple wine base with natural sugar-containing substances, preserving fresh apple taste.",
        "desc_ru": "Вино изготовлено путем купажирования яблочного натурального столового обработанного виноматериала и сахаросодержащих веществ.",
        "abv": "11.0% - 12.9%", "sugar": "40 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.7L", "pack_ru": "Стеклянная бутылка 0,7л",
        "color_en": "From light amber to dark amber", "color_ru": "От светло-янтарного до темно-янтарного",
        "taste_en": "Harmonious, clean with pure apple notes", "taste_ru": "Гармоничный, чистый с яблочными тонами",
        "raw_en": "Natural apple wine material", "raw_ru": "Натуральный обработанный яблочный виноматериал"
    },
    {
        "id": 9, "slide": 13, "cat_en": "Wines & Vermouths", "cat_ru": "Вина и вермуты",
        "title_en": "Berry Quartet", "title_ru": "Ягодный квартет",
        "sub_en": "Natural Table Semi-Sweet Fruit Wine", "sub_ru": "Вино фруктово-ягодное натуральное столовое полусладкое «Ягодный квартет»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/quartet.png",
        "desc_en": "A rich 4-berry blend crafted from processed apple wine base infused with dried extracts of elderberry, cranberry, cowberry (lingonberry), and blackberry.",
        "desc_ru": "Вино изготовлено путем купажирования яблочного натурального столового обработанного виноматериала, экстракта сухих ягод бузины, клюквы, брусники, ежевики и сахаросодержащих веществ.",
        "abv": "11.0% - 12.9%", "sugar": "45 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.7L", "pack_ru": "Стеклянная бутылка 0,7л",
        "color_en": "From light amber to dark amber with pinkish tint", "color_ru": "От светло-янтарного до темно-янтарного с розовым оттенком",
        "taste_en": "Harmonious, complex apple-berry bouquet", "taste_ru": "Гармоничный, с яблочно-ягодными тонами",
        "raw_en": "Apple wine base, extracts of elderberry, cranberry, cowberry, blackberry", "raw_ru": "Яблочный виноматериал, экстракты бузины, клюквы, брусники, ежевики"
    },
    {
        "id": 10, "slide": 14, "cat_en": "Fruit Ciders", "cat_ru": "Сидры",
        "title_en": "Apple Boom (Dry)", "title_ru": "Эпл Бум (Сухой)",
        "sub_en": "Sparkling Dry Fruit-Berry Cider", "sub_ru": "Сидр фруктово-ягодный газированный сухой «Эпл Бум» («Apple Boom»)",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/apple-boom-04-suh-481x1024.png",
        "desc_en": "Naturally fermented sparkling cider crafted from 100% natural apple juice. Delivers crisp acidity, fine effervescence, and clean dry fruit profile.",
        "desc_ru": "Сидр фруктово-ягодный газированный сухой «Эпл Бум». Изготовлен путем сбраживания натурального яблочного сока. Обладает свежим сухим вкусом и естественной газацией.",
        "abv": "5.7%", "sugar": "≤ 3.0 g/dm³", "shelf": "16 months", "pack_en": "Glass bottle 0.4L", "pack_ru": "Стеклянная бутылка 0,4л",
        "color_en": "From light straw to amber", "color_ru": "От светло-соломенного до янтарного",
        "taste_en": "Harmonious, fresh apple flavor", "taste_ru": "Гармоничный, яблочный",
        "raw_en": "100% natural fermented apple juice", "raw_ru": "100% натуральный сброженный яблочный сок"
    },
    {
        "id": 11, "slide": 15, "cat_en": "Fruit Ciders", "cat_ru": "Сидры",
        "title_en": "Apple Boom (Semi-Sweet)", "title_ru": "Эпл Бум (Полусладкий)",
        "sub_en": "Sparkling Semi-Sweet Fruit-Berry Cider", "sub_ru": "Сидр фруктово-ягодный газированный полусладкий «Эпл Бум» («Apple Boom»)",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/apple-boom-04-p-sl-481x1024.png",
        "desc_en": "Naturally fermented sparkling semi-sweet cider with a rich golden hue and vibrant crisp apple sweetness, perfect for modern refreshing aperitifs.",
        "desc_ru": "Сидр фруктово-ягодный газированный полусладкий «Эпл Бум». Натуральный сброженный напиток с мягким полусладким яблочным вкусом и приятным ароматом.",
        "abv": "5.7%", "sugar": "50.0 g/dm³", "shelf": "16 months", "pack_en": "Glass bottle 0.4L", "pack_ru": "Стеклянная бутылка 0,4л",
        "color_en": "From light straw to amber", "color_ru": "От светло-соломенного до янтарного",
        "taste_en": "Harmonious, sweet juicy apple bouquet", "taste_ru": "Гармоничный, сочный яблочный",
        "raw_en": "100% natural fermented apple juice", "raw_ru": "100% натуральный сброженный яблочный сок"
    },
    {
        "id": 12, "slide": 16, "cat_en": "Low-Alcohol Beverages", "cat_ru": "Слабоалкогольные напитки",
        "title_en": "Chinazes Cherry & Cinnamon", "title_ru": "Чиназес со вкусом Вишни",
        "sub_en": "Natural Carbonated Low-Alcohol Beverage", "sub_ru": "Напиток слабоалкогольный натуральный газированный «ЧИНАЗЕС СО ВКУСОМ ВИШНИ»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chinazesvishnja04-481x1024.png",
        "desc_en": "Trendy natural carbonated low-alcohol drink blending sweet ripe cherry with subtle honey notes and warm spice hints of cinnamon.",
        "desc_ru": "Напиток слабоалкогольный натуральный газированный со вкусом вишни. Гармоничное сочетание спелой вишни, медового оттенка и пряных ноток корицы.",
        "abv": "5.0%", "sugar": "70 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.4L", "pack_ru": "Стеклянная бутылка 0,4л",
        "color_en": "From pink to red", "color_ru": "От розового до красного",
        "taste_en": "Harmonious, cherry with honey notes and cinnamon hints", "taste_ru": "Гармоничный, вишнёвый с медовым оттенком и нотами корицы",
        "raw_en": "Natural juice base, natural cherry flavor, honey and cinnamon extract", "raw_ru": "Натуральная соковая основа, ароматизатор вишни, экстракт меда и корицы"
    },
    {
        "id": 13, "slide": 17, "cat_en": "Low-Alcohol Beverages", "cat_ru": "Слабоалкогольные напитки",
        "title_en": "Chinazes Pear & Honey", "title_ru": "Чиназес со вкусом Груши",
        "sub_en": "Natural Carbonated Low-Alcohol Beverage", "sub_ru": "Напиток слабоалкогольный натуральный газированный «ЧИНАЗЕС СО ВКУСОМ ГРУШИ»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chinazesgrusha04-481x1024.png",
        "desc_en": "Refreshing sparkling low-alcohol beverage featuring juicy Duchess pear aroma balanced with delicate golden honey sweetness.",
        "desc_ru": "Напиток слабоалкогольный натуральный газированный со вкусом груши. Освежающий вкус сочной груши дюшес со сбалансированной медовой сладостью.",
        "abv": "5.0%", "sugar": "70 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.4L", "pack_ru": "Стеклянная бутылка 0,4л",
        "color_en": "From straw to amber", "color_ru": "От соломенного до янтарного",
        "taste_en": "Harmonious, pear with honey undertones", "taste_ru": "Гармоничный, грушевый с медовым оттенком",
        "raw_en": "Natural juice base, pear flavor, natural honey extract", "raw_ru": "Натуральная соковая основа, грушевый ароматизатор, натуральный экстракт меда"
    },
    {
        "id": 14, "slide": 18, "cat_en": "Low-Alcohol Beverages", "cat_ru": "Слабоалкогольные напитки",
        "title_en": "Chinazes Lemon & Honey", "title_ru": "Чиназес со вкусом Лимона",
        "sub_en": "Natural Carbonated Low-Alcohol Beverage", "sub_ru": "Напиток слабоалкогольный натуральный газированный «ЧИНАЗЕС СО ВКУСОМ ЛИМОНА»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chinazeslimon04-481x1024.png",
        "desc_en": "Invigorating carbonated drink combining zesty Sicilian lemon citrus sharpness with smooth, soothing honey sweetness.",
        "desc_ru": "Напиток слабоалкогольный натуральный газированный со вкусом лимона. Бодрящий цитрусовый вкус лимона с мягкой медовой нотой.",
        "abv": "5.0%", "sugar": "70 g/dm³", "shelf": "12 months", "pack_en": "Glass bottle 0.4L", "pack_ru": "Стеклянная бутылка 0,4л",
        "color_en": "From light yellow to lemon yellow", "color_ru": "От светло-жёлтого до лимонно-жёлтого",
        "taste_en": "Harmonious, citrus lemon with honey hints", "taste_ru": "Гармоничный, лимонный с медовым оттенком",
        "raw_en": "Natural juice base, citrus lemon extract, natural honey extract", "raw_ru": "Натуральная соковая основа, экстракт лимона, натуральный экстракт меда"
    },
    {
        "id": 15, "slide": 19, "cat_en": "Youth & Lifestyle Line", "cat_ru": "Молодежная линейка",
        "title_en": "Zero Bianco (Non-Alcoholic)", "title_ru": "Zero Bianco (Безалкогольный)",
        "sub_en": "Non-Alcoholic Lifestyle Beverage", "sub_ru": "Безалкогольный напиток «Zero Bianco»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202435-683x1024.png",
        "desc_en": "Zero alcohol — 100% freedom. Created for drive, sports, active living, and clean taste. Light and crisp like the morning after victory.",
        "desc_ru": "Ноль алкоголя — сто процентов свободы. Для тех, кто выбирает драйв, спорт, движение и чистый вкус жизни. Лёгкий, свежий, как утро после победы.",
        "abv": "0.0%", "sugar": "Not specified in official source", "shelf": "12 months", "pack_en": "Glass bottle 0.75L", "pack_ru": "Стеклянная бутылка 0,75л",
        "color_en": "Crystal clear, light straw", "color_ru": "Кристально-прозрачный, светло-соломенный",
        "taste_en": "Crisp, refreshing, light grape-citrus notes", "taste_ru": "Свежий, бодрящий, легкие виноградно-цитрусовые тона",
        "raw_en": "Dealcoholized juice base, botanical extracts", "raw_ru": "Деалкоголизированная соковая основа, растительные экстракты"
    },
    {
        "id": 16, "slide": 20, "cat_en": "Youth & Lifestyle Line", "cat_ru": "Молодежная линейка",
        "title_en": "Rosso Allegro", "title_ru": "Rosso Allegro",
        "sub_en": "Vibrant Red Lifestyle Beverage", "sub_ru": "Виноградный напиток «Rosso Allegro»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202236-683x1024.png",
        "desc_en": "Red means passion. Made for those who aren't afraid to shine bright. Every sip bursts with emotion like a dance under night lights.",
        "desc_ru": "Красное — значит страсть. Это напиток для тех, кто не боится быть ярким. Каждый глоток — как вспышка эмоций, как танец под любимый трек.",
        "abv": "Not specified in official source", "sugar": "Not specified in official source", "shelf": "12 months", "pack_en": "Glass bottle 0.75L", "pack_ru": "Стеклянная бутылка 0,75л",
        "color_en": "Deep vibrant ruby red", "color_ru": "Насыщенный рубиново-красный",
        "taste_en": "Passionate, rich red berry bouquet", "taste_ru": "Страстный, сочный вкус красных ягод",
        "raw_en": "Natural grape and berry base", "raw_ru": "Натуральная виноградно-ягодная основа"
    },
    {
        "id": 17, "slide": 21, "cat_en": "Youth & Lifestyle Line", "cat_ru": "Молодежная линейка",
        "title_en": "Belloro", "title_ru": "Belloro",
        "sub_en": "Classic White Lifestyle Beverage", "sub_ru": "Напиток винный «Belloro»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202908-683x1024.png",
        "desc_en": "White classic with character. Pure, fresh, confident — for people who know what they want. Perfect for gatherings starting with a smile.",
        "desc_ru": "Белая классика с характером. Чистый, свежий, уверенный — как человек, который знает, чего хочет. Идеален для встреч, где всё начинается с улыбки.",
        "abv": "Not specified in official source", "sugar": "Not specified in official source", "shelf": "12 months", "pack_en": "Glass bottle 0.75L", "pack_ru": "Стеклянная бутылка 0,75л",
        "color_en": "Golden straw with green highlights", "color_ru": "Золотисто-соломенный с зеленоватым оттенком",
        "taste_en": "Clean, confident, well-balanced fruity taste", "taste_ru": "Чистый, уверенный, сбалансированный фруктовый вкус",
        "raw_en": "Natural white fruit wine material", "raw_ru": "Натуральный белый фруктовый виноматериал"
    },
    {
        "id": 18, "slide": 22, "cat_en": "Youth & Lifestyle Line", "cat_ru": "Молодежная линейка",
        "title_en": "Lumino", "title_ru": "Lumino",
        "sub_en": "Light Rosé Lifestyle Beverage", "sub_ru": "Напиток розовый «Lumino»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202117-683x1024.png",
        "desc_en": "Pink radiance, lightness, and flirtation. Designed for those living in the rhythm of sunset parties. Tastes like a cool breeze with friends.",
        "desc_ru": "Розовое сияние, лёгкость и флирт. Создан для тех, кто живёт в ритме вечеринок и закатов. Вкус — как первый глоток после смеха с друзьями.",
        "abv": "Not specified in official source", "sugar": "Not specified in official source", "shelf": "12 months", "pack_en": "Glass bottle 0.75L", "pack_ru": "Стеклянная бутылка 0,75л",
        "color_en": "Delicate glowing rose pink", "color_ru": "Нежный сияющий розовый",
        "taste_en": "Light, subtle floral-berry notes", "taste_ru": "Легкий, утонченный с цветочно-ягодными оттенками",
        "raw_en": "Natural light fruit blend", "raw_ru": "Натуральный светлый фруктовый купаж"
    },
    {
        "id": 19, "slide": 23, "cat_en": "Potato & Modified Starches", "cat_ru": "Картофельный и модифицированный крахмал",
        "title_en": "Potato Starch (GOST 7699-78)", "title_ru": "Крахмал картофельный (ГОСТ 7699-78)",
        "sub_en": "Grades: Extra, Superior, 1st Grade, 2nd Grade", "sub_ru": "Сорта: «Экстра», «Высший», «Первый», «Второй»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/01/ChatGPT-Image-25-янв.-2026-г.-15_15_05-1024x683.png",
        "desc_en": "High-purity natural potato starch produced in 4 official grades under GOST 7699-78. Widely used in confectionery, meat/dairy processing, bakery, pharmaceutical tablet filling, dextrin production, textile, and paper industries.",
        "desc_ru": "Высокоочищенный натуральный картофельный крахмал четырех сортов по ГОСТ 7699-78. Применяется в кондитерской, мясо-молочной, хлебопекарной, фармацевтической, текстильной и бумажной отраслях.",
        "abv": "0.0% (Food Grade)", "sugar": "0.0 g/dm³", "shelf": "24 months", "pack_en": "PP bags with PE liner 40kg / Paper packets 500g", "pack_ru": "Мешки ПП с ПЭ вкладышем 40 кг / Бумажные пакеты 500г",
        "color_en": "White to off-white powder with crystalline lustre", "color_ru": "Белый порошок с кристаллическим блеском",
        "taste_en": "Neutral odor and taste characteristic of potato starch", "taste_ru": "Нейтральный запах и вкус, свойственный крахмалу",
        "raw_en": "100% selected agricultural potatoes", "raw_ru": "100% отборный продовольственный картофель"
    },
    {
        "id": 20, "slide": 24, "cat_en": "Potato & Modified Starches", "cat_ru": "Картофельный и модифицированный крахмал",
        "title_en": "Oxidized Starch 'Oxyamyl OPV' (E 1404)", "title_ru": "Крахмал окисленный «Оксиамил ОПВ» (E 1404)",
        "sub_en": "Food Additive / Modified Starch (TU BY 690277551.003-2006)", "sub_ru": "Пищевая добавка / Модифицированный крахмал ТУ BY 690277551.003-2006",
        "img": "https://alco.by/instal/wp-content/uploads/2026/01/mFruSFX2M8AU.png",
        "desc_en": "Modified oxidized potato starch with high gelling capabilities. Functions as emulsifier, thickener, binder, and carrier in ice cream, jelly products, Turkish delight, and industrial technical applications.",
        "desc_ru": "Крахмал окисленный обладает желирующими способностями и применяется в качестве эмульгатора, загустителя, связующего при производстве мороженого, желейных изделий, лукума и технических целей.",
        "abv": "0.0% (Food Additive)", "sugar": "0.0 g/dm³", "shelf": "24 months", "pack_en": "Polypropylene bags with PE liner 40 kg", "pack_ru": "Мешки полипропиленовые с ПЭ вкладышем 40 кг",
        "color_en": "White fine uniform powder", "color_ru": "Белый однородный порошок",
        "taste_en": "Neutral, free from foreign odor", "taste_ru": "Нейтральный, без посторонних запахов",
        "raw_en": "Chemically modified potato starch E 1404", "raw_ru": "Модифицированный картофельный крахмал Е 1404"
    }
]

def generate_product_slide(p):
    return f'''
        <!-- SLIDE {p["slide"]}: {p["title_en"]} -->
        <section class="slide" id="slide-{p["slide"]}">
            <div class="product-card-container">
                <div class="product-card-header">
                    <div class="product-badge">
                        <span class="product-cat-tag" data-en="{p["cat_en"]}" data-ru="{p["cat_ru"]}">{p["cat_en"]}</span>
                        <span class="product-num-tag">NO. {p["id"]:02d} / 20</span>
                    </div>
                    <img src="https://alco.by/instal/wp-content/uploads/2026/01/200-3.png" alt="Veselovo Logo" class="product-card-logo">
                </div>

                <div class="product-card-body">
                    <div class="product-img-wrapper">
                        <img src="{p["img"]}" alt="{p["title_en"]}" class="product-main-img">
                    </div>

                    <div class="product-details-wrapper">
                        <h2 class="product-title" data-en="{p["title_en"]}" data-ru="{p["title_ru"]}">{p["title_en"]}</h2>
                        <div class="product-subtitle" data-en="{p["sub_en"]}" data-ru="{p["sub_ru"]}">{p["sub_en"]}</div>
                        <p class="product-desc" data-en="{p["desc_en"]}" data-ru="{p["desc_ru"]}">{p["desc_en"]}</p>

                        <div class="specs-grid">
                            <div class="spec-box">
                                <span class="spec-label" data-en="Alcohol By Volume" data-ru="Крепость (спирт)">Alcohol By Volume</span>
                                <span class="spec-value">{p["abv"]}</span>
                            </div>
                            <div class="spec-box">
                                <span class="spec-label" data-en="Sugar Content" data-ru="Содержание сахара">Sugar Content</span>
                                <span class="spec-value">{p["sugar"]}</span>
                            </div>
                            <div class="spec-box">
                                <span class="spec-label" data-en="Shelf Life" data-ru="Срок годности">Shelf Life</span>
                                <span class="spec-value">{p["shelf"]}</span>
                            </div>
                            <div class="spec-box">
                                <span class="spec-label" data-en="Packaging" data-ru="Упаковка и фасовка">Packaging</span>
                                <span class="spec-value-small" data-en="{p["pack_en"]}" data-ru="{p["pack_ru"]}">{p["pack_en"]}</span>
                            </div>
                            <div class="spec-box full-width">
                                <span class="spec-label" data-en="Color & Visuals" data-ru="Цвет">Color & Visuals</span>
                                <span class="spec-value-small" data-en="{p["color_en"]}" data-ru="{p["color_ru"]}">{p["color_en"]}</span>
                            </div>
                            <div class="spec-box full-width">
                                <span class="spec-label" data-en="Taste & Aroma Profile" data-ru="Вкус и аромат">Taste & Aroma Profile</span>
                                <span class="spec-value-small" data-en="{p["taste_en"]}" data-ru="{p["taste_ru"]}">{p["taste_en"]}</span>
                            </div>
                            <div class="spec-box full-width">
                                <span class="spec-label" data-en="Raw Ingredients" data-ru="Состав и сырье">Raw Ingredients</span>
                                <span class="spec-value-small" data-en="{p["raw_en"]}" data-ru="{p["raw_ru"]}">{p["raw_en"]}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    '''

products_html = "".join([generate_product_slide(p) for p in products_data])

full_document = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VESELOVO — Product Portfolio | OJSC "Food Plant 'Veselovo'"</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <style>
        :root {{
            --bg-color: #FFFFFF;
            --text-primary: #111111;
            --text-secondary: #555555;
            --text-muted: #888888;
            --accent-green: #2E7D32;
            --accent-green-light: #E8F5E9;
            --accent-green-border: #C8E6C9;
            --border-color: #E5E5E5;
            --border-light: #F0F0F0;
            --card-bg: #FFFFFF;
            --font-main: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-title: 'Playfair Display', Georgia, serif;
            --header-height: 70px;
        }}

        * {{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }}

        html, body {{
            background-color: #FFFFFF !important;
            color: var(--text-primary);
            font-family: var(--font-main);
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }}

        header.top-bar {{
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            z-index: 1000;
        }}

        .header-brand {{
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--text-primary);
        }}

        .header-logo {{
            height: 42px;
            width: auto;
            object-fit: contain;
        }}

        .header-title-group {{
            display: flex;
            flex-direction: column;
        }}

        .header-title {{
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }}

        .header-subtitle {{
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }}

        .header-controls {{
            display: flex;
            align-items: center;
            gap: 16px;
        }}

        .lang-switch {{
            display: flex;
            background: var(--border-light);
            border-radius: 20px;
            padding: 3px;
            border: 1px solid var(--border-color);
        }}

        .lang-btn {{
            border: none;
            background: transparent;
            padding: 5px 14px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
        }}

        .lang-btn.active {{
            background: #FFFFFF;
            color: var(--accent-green);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }}

        .view-controls {{
            display: flex;
            gap: 8px;
        }}

        .ctrl-btn {{
            border: 1px solid var(--border-color);
            background: #FFFFFF;
            color: var(--text-primary);
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }}

        .ctrl-btn:hover {{
            border-color: var(--accent-green);
            color: var(--accent-green);
        }}

        .ctrl-btn.active {{
            background: var(--accent-green);
            border-color: var(--accent-green);
            color: #FFFFFF;
        }}

        .slide-nav-bar {{
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 30px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 999;
        }}

        .slide-nav-btn {{
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--text-primary);
            padding: 4px 10px;
            border-radius: 50%;
            transition: background 0.2s ease;
        }}

        .slide-nav-btn:hover {{
            background: var(--border-light);
            color: var(--accent-green);
        }}

        .slide-counter {{
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--text-secondary);
            min-width: 110px;
            text-align: center;
        }}

        main.presentation-container {{
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
            background: #FFFFFF !important;
        }}

        .slide {{
            width: 100%;
            min-height: calc(100vh - var(--header-height));
            padding: 60px 80px 100px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-bottom: 1px solid var(--border-light);
            background: #FFFFFF !important;
            position: relative;
            box-sizing: border-box;
        }}

        body.mode-slides .slide {{
            display: none;
        }}

        body.mode-slides .slide.active-slide {{
            display: flex;
            animation: fadeIn 0.3s ease-in-out;
        }}

        @keyframes fadeIn {{
            from {{ opacity: 0; transform: translateY(6px); }}
            to {{ opacity: 1; transform: translateY(0); }}
        }}

        h1.slide-title-hero {{
            font-family: var(--font-title);
            font-size: 46px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.15;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }}

        h2.section-header {{
            font-family: var(--font-title);
            font-size: 34px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
        }}

        .section-tag {{
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent-green);
            background: var(--accent-green-light);
            padding: 6px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            border: 1px solid var(--accent-green-border);
        }}

        p.section-desc {{
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 800px;
            margin-bottom: 30px;
            line-height: 1.7;
        }}

        .grid-2col {{
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }}

        .grid-3col {{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }}

        .grid-4col {{
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }}

        .info-card {{
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            background: #FFFFFF;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }}

        .info-card:hover {{
            border-color: var(--accent-green);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }}

        .info-card h3 {{
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-primary);
        }}

        .info-card p {{
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }}

        .product-card-container {{
            display: flex;
            flex-direction: column;
            gap: 20px;
            height: 100%;
        }}

        .product-card-header {{
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 12px;
        }}

        .product-badge {{
            display: flex;
            align-items: center;
            gap: 12px;
        }}

        .product-cat-tag {{
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent-green);
            background: var(--accent-green-light);
            padding: 4px 12px;
            border-radius: 4px;
            border: 1px solid var(--accent-green-border);
        }}

        .product-num-tag {{
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 1px;
        }}

        .product-card-logo {{
            height: 32px;
            object-fit: contain;
        }}

        .product-card-body {{
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 50px;
            align-items: center;
        }}

        .product-img-wrapper {{
            background: #FFFFFF;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 420px;
            position: relative;
        }}

        .product-main-img {{
            max-height: 380px;
            max-width: 100%;
            object-fit: contain;
            filter: drop-shadow(0 12px 24px rgba(0,0,0,0.08));
            transition: transform 0.3s ease;
        }}

        .product-img-wrapper:hover .product-main-img {{
            transform: scale(1.03);
        }}

        .product-details-wrapper {{
            display: flex;
            flex-direction: column;
            gap: 16px;
        }}

        .product-title {{
            font-family: var(--font-title);
            font-size: 32px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.2;
        }}

        .product-subtitle {{
            font-size: 14px;
            font-weight: 600;
            color: var(--accent-green);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }}

        .product-desc {{
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.65;
        }}

        .specs-grid {{
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 10px;
        }}

        .spec-box {{
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 14px;
            background: #FFFFFF;
        }}

        .spec-box.full-width {{
            grid-column: span 2;
        }}

        .spec-label {{
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 2px;
        }}

        .spec-value {{
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }}

        .spec-value-small {{
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }}

        .clean-table-wrapper {{
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-top: 20px;
        }}

        table.clean-table {{
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }}

        table.clean-table th {{
            background: var(--border-light);
            color: var(--text-primary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
        }}

        table.clean-table td {{
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-secondary);
        }}

        table.clean-table tr:last-child td {{
            border-bottom: none;
        }}

        table.clean-table tr:hover td {{
            background: var(--accent-green-light);
            color: var(--text-primary);
        }}

        .cover-hero-grid {{
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 60px;
            align-items: center;
        }}

        .cover-img-montage {{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: -20px;
            background: #FFFFFF;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
        }}

        .cover-montage-item {{
            height: 320px;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.08));
        }}

        @media (max-width: 1024px) {{
            .slide {{
                padding: 40px 30px 80px 30px;
            }}
            .product-card-body, .cover-hero-grid, .grid-2col {{
                grid-template-columns: 1fr;
                gap: 24px;
            }}
            .grid-3col, .grid-4col {{
                grid-template-columns: 1fr 1fr;
            }}
            .product-img-wrapper {{
                min-height: 280px;
            }}
        }}

        @media (max-width: 600px) {{
            header.top-bar {{
                padding: 0 16px;
            }}
            .header-subtitle, .view-controls span:last-child {{
                display: none;
            }}
            .grid-3col, .grid-4col, .specs-grid {{
                grid-template-columns: 1fr;
            }}
            .spec-box.full-width {{
                grid-column: span 1;
            }}
        }}

        @media print {{
            header.top-bar, .slide-nav-bar {{ display: none !important; }}
            main.presentation-container {{ margin-top: 0 !important; }}
            .slide {{ display: flex !important; page-break-after: always; padding: 30px !important; }}
        }}
    </style>
</head>
<body class="mode-slides">

    <!-- Top Fixed Header -->
    <header class="top-bar">
        <a href="#slide-1" class="header-brand" onclick="showSlide(1)">
            <img src="https://alco.by/instal/wp-content/uploads/2026/01/200-3.png" alt="Veselovo Logo" class="header-logo">
            <div class="header-title-group">
                <span class="header-title">VESELOVO</span>
                <span class="header-subtitle" data-en="Product Portfolio" data-ru="Каталог Продукции">Product Portfolio</span>
            </div>
        </a>

        <div class="header-controls">
            <!-- Language Switcher -->
            <div class="lang-switch">
                <button class="lang-btn active" id="btn-en" onclick="switchLanguage('en')">EN</button>
                <button class="lang-btn" id="btn-ru" onclick="switchLanguage('ru')">RU</button>
            </div>

            <!-- View Mode Controls -->
            <div class="view-controls">
                <button class="ctrl-btn active" id="mode-slides-btn" onclick="setViewMode('slides')">
                    <span>🖥️</span> <span data-en="Slides" data-ru="Слайды">Slides</span>
                </button>
                <button class="ctrl-btn" id="mode-scroll-btn" onclick="setViewMode('scroll')">
                    <span>📜</span> <span data-en="Scroll View" data-ru="Сплошной вид">Scroll View</span>
                </button>
                <button class="ctrl-btn" onclick="toggleFullscreen()">
                    <span>⛶</span> <span data-en="Full Screen" data-ru="Полный экран">Full Screen</span>
                </button>
                <button class="ctrl-btn" onclick="window.print()">
                    <span>🖨️</span> <span data-en="Print / PDF" data-ru="Печать / PDF">Print / PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Slide Navigation Bar -->
    <nav class="slide-nav-bar" id="slideNav">
        <button class="slide-nav-btn" onclick="prevSlide()" title="Previous Slide (Left Arrow)">◀</button>
        <span class="slide-counter" id="slideCounter">SLIDE 1 / 36</span>
        <button class="slide-nav-btn" onclick="nextSlide()" title="Next Slide (Right Arrow / Space)">▶</button>
    </nav>

    <!-- Main Presentation Container -->
    <main class="presentation-container" id="presentationContainer">

        <!-- SLIDE 1: COVER -->
        <section class="slide active-slide" id="slide-1">
            <div class="cover-hero-grid">
                <div>
                    <span class="section-tag" data-en="Official Enterprise Portfolio" data-ru="Официальный каталог предприятия">Official Enterprise Portfolio</span>
                    <h1 class="slide-title-hero" data-en="VESELOVO — PRODUCT PORTFOLIO" data-ru="«ВЕСЕЛОВО» — КАТАЛОГ ПРОДУКЦИИ">VESELOVO — PRODUCT PORTFOLIO</h1>
                    <p class="section-desc" data-en="Official B2B product catalog of OJSC 'Food Plant Veselovo' — high-purity natural fruit wines, vermouths, ciders, trendy carbonated drinks, and industrial potato starches." data-ru="Официальный B2B каталог продукции ОАО «Пищевой комбинат „Веселово“» — высококачественные плодовые вина, вермуты, сидры, слабоалкогольные напитки и картофельный крахмал.">Official B2B product catalog of OJSC 'Food Plant Veselovo' — high-purity natural fruit wines, vermouths, ciders, trendy carbonated drinks, and industrial potato starches.</p>
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <img src="https://alco.by/instal/wp-content/uploads/2026/01/200-3.png" alt="Veselovo Logo" style="height: 50px; object-fit: contain;">
                        <div style="font-size: 13px; color: var(--text-secondary);">
                            <strong data-en="OJSC Food Plant Veselovo" data-ru="ОАО «Пищевой комбинат „Веселово“»">OJSC Food Plant Veselovo</strong><br>
                            <span data-en="Member of MINSK KRISTALL GROUP Holding" data-ru="Входит в холдинг «МИНСК КРИСТАЛЛ ГРУПП»">Member of MINSK KRISTALL GROUP Holding</span>
                        </div>
                    </div>
                </div>
                <div class="cover-img-montage">
                    <img src="https://alco.by/instal/wp-content/uploads/2026/02/vermout_rubin.png" alt="Vermout Rubin" class="cover-montage-item">
                    <img src="https://alco.by/instal/wp-content/uploads/2026/02/apple-boom-04-suh-481x1024.png" alt="Apple Boom" class="cover-montage-item">
                    <img src="https://alco.by/instal/wp-content/uploads/2026/02/chinazesvishnja04-481x1024.png" alt="Chinazes" class="cover-montage-item">
                </div>
            </div>
        </section>

        <!-- SLIDE 2: ABOUT ENTERPRISE -->
        <section class="slide" id="slide-2">
            <span class="section-tag" data-en="About Enterprise" data-ru="О предприятии">About Enterprise</span>
            <h2 class="section-header" data-en="Centenary Heritage & State Quality" data-ru="Вековая история и государственные стандарты">Centenary Heritage & State Quality</h2>
            <p class="section-desc" data-en="OJSC 'Food Plant Veselovo' is a premier agro-industrial manufacturer in Belarus specializing in natural fruit wines, ciders, low-alcohol beverages, and refined potato starches." data-ru="ОАО «Пищевой комбинат „Веселово“» — ведущее перерабатывающее предприятие Беларуси, специализирующееся на производстве натуральных вин, сидров и крахмалопродуктов.">OJSC 'Food Plant Veselovo' is a premier agro-industrial manufacturer in Belarus specializing in natural fruit wines, ciders, low-alcohol beverages, and refined potato starches.</p>
            <div class="grid-3col">
                <div class="info-card">
                    <h3 data-en="🏛️ Corporate Status" data-ru="🏛️ Статус и холдинг">🏛️ Corporate Status</h3>
                    <p data-en="Member of 'MINSK KRISTALL GROUP' holding under Decree No. 326 of the President of the Republic of Belarus. Subordinate to state concern 'BELGOSPISCHEPROM'." data-ru="Входит в состав алкогольного холдинга «МИНСК КРИСТАЛЛ ГРУПП» (Указ Президента РБ №326) и подчиняется концерну «Белгоспищепром».">Member of 'MINSK KRISTALL GROUP' holding under Decree No. 326 of the President of the Republic of Belarus. Subordinate to state concern 'BELGOSPISCHEPROM'.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="📜 Quality Certifications" data-ru="📜 Сертификация качества">📜 Quality Certifications</h3>
                    <p data-en="Certified under ISO 9001-2015 quality management system and HACCP food safety standards, guaranteeing rigorous control from farm raw materials to finished bottle." data-ru="Производство сертифицировано по международным системам СТБ ISO 9001-2015 и НАССР, гарантирующим сквозной контроль качества.">Certified under ISO 9001-2015 quality management system and HACCP food safety standards, guaranteeing rigorous control from farm raw materials to finished bottle.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🍎 Raw Materials & Traditions" data-ru="🍎 Сырье и традиции">🍎 Raw Materials & Traditions</h3>
                    <p data-en="Built upon 100% natural Belarusian fruits and berries (apples, chokeberry, blackcurrant, blueberry, cranberry) harvested from ecologically clean regions." data-ru="Основой продукции являются дары белорусской земли — натуральные свежие ягоды и фрукты (яблоки, черника, смородина, клюква).">Built upon 100% natural Belarusian fruits and berries (apples, chokeberry, blackcurrant, blueberry, cranberry) harvested from ecologically clean regions.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 3: HISTORY TIMELINE -->
        <section class="slide" id="slide-3">
            <span class="section-tag" data-en="Historical Milestones" data-ru="История развития">Historical Milestones</span>
            <h2 class="section-header" data-en="From 1925 Agricultural Processing to Modern Holding" data-ru="От переработки сельхозсырья 1925 года до холдинга">From 1925 Agricultural Processing to Modern Holding</h2>
            <div class="grid-4col" style="margin-top: 20px;">
                <div class="info-card">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-green);">1925 – 1927</div>
                    <h3 data-en="Plant Foundation" data-ru="Основание завода">Plant Foundation</h3>
                    <p data-en="Construction commenced in Veselovo village. In 1927, agricultural processing workshop launched with 20 tons/day potato processing capacity." data-ru="Начало строительства в д. Веселово. В 1927 г. запущен цех переработки сельхозсырья мощностью 20 т картофеля в сутки.">Construction commenced in Veselovo village. In 1927, agricultural processing workshop launched with 20 tons/day potato processing capacity.</p>
                </div>
                <div class="info-card">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-green);">1930s – 1960s</div>
                    <h3 data-en="Wine Workshop Expansion" data-ru="Запуск винпроизводства">Wine Workshop Expansion</h3>
                    <p data-en="Inauguration of wine production facilities and fruit juice processing lines, setting regional standards for fortified fruit wines." data-ru="Открытие винодельческого цеха и линий переработки плодово-ягодного сырья, формирование собственных рецептур.">Inauguration of wine production facilities and fruit juice processing lines, setting regional standards for fortified fruit wines.</p>
                </div>
                <div class="info-card">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-green);">2000s – 2015</div>
                    <h3 data-en="Holding Integration" data-ru="Вхождение в холдинг">Holding Integration</h3>
                    <p data-en="Modernization of production technology. In July 2015, joined 'MINSK KRISTALL GROUP' holding under Presidential Decree No. 326." data-ru="Модернизация оборудования. В июле 2015 г. предприятие вошло в состав холдинга «МИНСК КРИСТАЛЛ ГРУПП».">Modernization of production technology. In July 2015, joined 'MINSK KRISTALL GROUP' holding under Presidential Decree No. 326.</p>
                </div>
                <div class="info-card">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-green);">2020s – Present</div>
                    <h3 data-en="New Portfolio & Ciders" data-ru="Современный ассортимент">New Portfolio & Ciders</h3>
                    <p data-en="Launch of 'Apple Boom' ciders, 'Chinazes' natural RTD beverages, lifestyle youth drinks, and premium GOST starches." data-ru="Освоение сидров «Apple Boom», слабоалкогольной линейки «Chinazes», молодежной серии и модифицированного крахмала.">Launch of 'Apple Boom' ciders, 'Chinazes' natural RTD beverages, lifestyle youth drinks, and premium GOST starches.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 4: ASSORTMENT STRUCTURE OVERVIEW -->
        <section class="slide" id="slide-4">
            <span class="section-tag" data-en="Product Portfolio Structure" data-ru="Структура ассортимента">Product Portfolio Structure</span>
            <h2 class="section-header" data-en="5 Core Strategic Product Categories" data-ru="5 основных категорий продукции">5 Core Strategic Product Categories</h2>
            <p class="section-desc" data-en="The full product portfolio of OJSC 'Food Plant Veselovo' spans 20 confirmed catalog SKUs across alcoholic, non-alcoholic, and industrial starch sectors." data-ru="Полный ассортимент ОАО «Пищевой комбинат „Веселово“» включает 20 подтвержденных наименований продукции.">The full product portfolio of OJSC 'Food Plant Veselovo' spans 20 confirmed catalog SKUs across alcoholic, non-alcoholic, and industrial starch sectors.</p>
            <div class="grid-3col">
                <div class="info-card">
                    <h3 data-en="🍷 Fruit Wines & Vermouths" data-ru="🍷 Плодовые вина и вермуты">🍷 Fruit Wines & Vermouths</h3>
                    <p data-en="9 SKUs including Vermouth Crystal Premium (Ruby & Blueberry), Sudarushka, Kirmash (Silver Medal), Antal, Black Lady, Nocturne, Backouskaye, and Berry Quartet." data-ru="9 позиций: вермуты «Кристалл Премиум», «Сударушка», медалист «Кiрмаш», «Анталь», «Черная дама», «Ягодный ноктюрн», «Бацькоускае» и «Ягодный квартет».">9 SKUs including Vermouth Crystal Premium (Ruby & Blueberry), Sudarushka, Kirmash (Silver Medal), Antal, Black Lady, Nocturne, Backouskaye, and Berry Quartet.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🍏 Natural Fruit Ciders" data-ru="🍏 Натуральные сидры">🍏 Natural Fruit Ciders</h3>
                    <p data-en="2 SKUs: 'Apple Boom' Dry and Semi-Sweet 5.7% ABV in sleek 0.4L glass bottles, naturally fermented from pure apple juice." data-ru="2 позиции: Сидры «Apple Boom» сухой и полусладкий (5,7% об.) в бутылках 0,4л, изготовленные путем естественного сбраживания.">2 SKUs: 'Apple Boom' Dry and Semi-Sweet 5.7% ABV in sleek 0.4L glass bottles, naturally fermented from pure apple juice.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🍒 Low-Alcohol Drinks" data-ru="🍒 Слабоалкогольные напитки">🍒 Low-Alcohol Drinks</h3>
                    <p data-en="3 SKUs: 'Chinazes' carbonated RTD series (5.0% ABV) featuring Cherry & Cinnamon, Pear & Honey, and Lemon & Honey in 0.4L glass." data-ru="3 позиции: Линейка газированных напитков «Chinazes» (5,0% об.) со вкусом вишни с корицей, груши с медом и лимона с медом.">3 SKUs: 'Chinazes' carbonated RTD series (5.0% ABV) featuring Cherry & Cinnamon, Pear & Honey, and Lemon & Honey in 0.4L glass.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="✨ Youth & Lifestyle Line" data-ru="✨ Молодежная линейка">✨ Youth & Lifestyle Line</h3>
                    <p data-en="4 SKUs: Zero Bianco (Non-Alcoholic 0.0%), Rosso Allegro, Belloro, and Lumino in elegant 0.75L wine glass bottles." data-ru="4 позиции: Безалкогольный Zero Bianco (0,0%), Rosso Allegro, Belloro и Lumino в элегантных бутылках 0,75л.">4 SKUs: Zero Bianco (Non-Alcoholic 0.0%), Rosso Allegro, Belloro и Lumino в элегантных бутылках 0,75л.</p>
                </div>
                <div class="info-card" style="grid-column: span 2;">
                    <h3 data-en="🥔 Potato & Modified Starches" data-ru="🥔 Картофельный и модифицированный крахмал">🥔 Potato & Modified Starches</h3>
                    <p data-en="2 Product lines: Potato Starch GOST 7699-78 (4 grades: Extra, Superior, 1st, 2nd) and Oxidized Starch 'Oxyamyl OPV' (Food additive E 1404) for food and industrial applications." data-ru="2 направления: Крахмал картофельный ГОСТ 7699-78 (4 сорта) и Крахмал окисленный «Оксиамил ОПВ» Е 1404 для пищевой и технической промышленности.">2 Product lines: Potato Starch GOST 7699-78 (4 grades: Extra, Superior, 1st, 2nd) and Oxidized Starch 'Oxyamyl OPV' (Food additive E 1404) for food and industrial applications.</p>
                </div>
            </div>
        </section>

{products_html}

        <!-- SLIDE 25: STARCH INDUSTRIAL SPECIFICATIONS -->
        <section class="slide" id="slide-25">
            <span class="section-tag" data-en="Starch Technical Standard" data-ru="Технический стандарт крахмала">Starch Technical Standard</span>
            <h2 class="section-header" data-en="Industrial Starch Technical Parameters" data-ru="Технические параметры крахмала">Industrial Starch Technical Parameters</h2>
            <p class="section-desc" data-en="Official physical and chemical specifications for Potato Starch GOST 7699-78 and Oxidized Starch E 1404." data-ru="Официальные физико-химические показатели картофельного крахмала ГОСТ 7699-78 и окисленного крахмала Е 1404.">Official physical and chemical specifications for Potato Starch GOST 7699-78 and Oxidized Starch E 1404.</p>
            <div class="clean-table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th data-en="Parameter / Property" data-ru="Показатель / Свойство">Parameter / Property</th>
                            <th data-en="Extra Grade" data-ru="Сорт Экстра">Extra Grade</th>
                            <th data-en="Superior Grade" data-ru="Высший сорт">Superior Grade</th>
                            <th data-en="1st Grade" data-ru="1-й сорт">1st Grade</th>
                            <th data-en="2nd Grade (Technical)" data-ru="2-й сорт (Технический)">2nd Grade (Technical)</th>
                            <th data-en="Oxidized Oxyamyl OPV" data-ru="Оксиамил ОПВ">Oxidized Oxyamyl OPV</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-en="Moisture Content (%)" data-ru="Массовая доля влаги (%)">Moisture Content (%)</td>
                            <td>≤ 20.0%</td>
                            <td>≤ 20.0%</td>
                            <td>≤ 20.0%</td>
                            <td>≤ 20.0%</td>
                            <td>≤ 20.0%</td>
                        </tr>
                        <tr>
                            <td data-en="Ash Content / Dry Matter (%)" data-ru="Зольность / Сухой остаток (%)">Ash Content / Dry Matter (%)</td>
                            <td>≤ 0.30%</td>
                            <td>≤ 0.35%</td>
                            <td>≤ 0.50%</td>
                            <td>≤ 1.00%</td>
                            <td>≤ 0.40%</td>
                        </tr>
                        <tr>
                            <td data-en="Acidity (cm³ 0.1M NaOH)" data-ru="Кислотность (см³ 0.1M NaOH)">Acidity (cm³ 0.1M NaOH)</td>
                            <td>≤ 6.0</td>
                            <td>≤ 10.0</td>
                            <td>≤ 14.0</td>
                            <td>≤ 20.0</td>
                            <td>≤ 8.0</td>
                        </tr>
                        <tr>
                            <td data-en="Specks per dm²" data-ru="Количество крапин на дм²">Specks per dm²</td>
                            <td>≤ 60</td>
                            <td>≤ 280</td>
                            <td>≤ 700</td>
                            <td>Not standardized</td>
                            <td>≤ 150</td>
                        </tr>
                        <tr>
                            <td data-en="Primary Application" data-ru="Основное применение">Primary Application</td>
                            <td data-en="Pharmaceuticals & High-End Food" data-ru="Фармацевтика и пищевая пром.">Pharmaceuticals & High-End Food</td>
                            <td data-en="Confectionery & Bakery" data-ru="Кондитерские изделия">Confectionery & Bakery</td>
                            <td data-en="Meat/Dairy & Food Processing" data-ru="Мясо-молочная пром.">Meat/Dairy & Food Processing</td>
                            <td data-en="Textile, Paper & Dextrin" data-ru="Текстиль, бумага, декстрин">Textile, Paper & Dextrin</td>
                            <td data-en="Jelly, Ice Cream, Emulsion" data-ru="Желе, мороженое, эмульсии">Jelly, Ice Cream, Emulsion</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SLIDE 26: WINE & VERMOUTH COMPARISON TABLE -->
        <section class="slide" id="slide-26">
            <span class="section-tag" data-en="Catalog Comparison" data-ru="Сравнение вин">Catalog Comparison</span>
            <h2 class="section-header" data-en="Wines & Vermouths Specification Matrix" data-ru="Сравнительная таблица вин и вермутов">Wines & Vermouths Specification Matrix</h2>
            <div class="clean-table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th data-en="Product Title" data-ru="Наименование напитка">Product Title</th>
                            <th data-en="Wine Category" data-ru="Категория вина">Wine Category</th>
                            <th data-en="ABV" data-ru="Крепость">ABV</th>
                            <th data-en="Sugar" data-ru="Сахар">Sugar</th>
                            <th data-en="Shelf Life" data-ru="Срок годности">Shelf Life</th>
                            <th data-en="Packaging" data-ru="Фасовка">Packaging</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Vermouth Crystal Premium Ruby</td><td data-en="Flavored Fortified Wine" data-ru="Ароматизированное вино">Flavored Fortified Wine</td><td>18.0%</td><td>60 g/dm³</td><td>18 months</td><td>0.5L / 0.7L</td></tr>
                        <tr><td>Vermouth Crystal Premium Blueberry</td><td data-en="Flavored Fortified Wine" data-ru="Ароматизированное вино">Flavored Fortified Wine</td><td>18.0%</td><td>65 g/dm³</td><td>18 months</td><td>0.5L / 0.7L</td></tr>
                        <tr><td>Sudarushka</td><td data-en="Fortified Strong Fruit Wine" data-ru="Плодовое крепленое">Fortified Strong Fruit Wine</td><td>18.0%</td><td>60 g/dm³</td><td>18 months</td><td>0.5L / 0.7L</td></tr>
                        <tr><td>Berry Nocturne</td><td data-en="Table Semi-Sweet Fruit Wine" data-ru="Столовое полусладкое">Table Semi-Sweet Fruit Wine</td><td>9.0-12.9%</td><td>50 g/dm³</td><td>12 months</td><td>0.7L</td></tr>
                        <tr><td>Kirmash (Silver Medal)</td><td data-en="Fortified Strong Fruit Wine" data-ru="Плодовое крепленое">Fortified Strong Fruit Wine</td><td>18.0%</td><td>70 g/dm³</td><td>12 months</td><td>0.5L / 0.7L</td></tr>
                        <tr><td>Black Lady</td><td data-en="Fortified Sweet Fruit Wine" data-ru="Плодовое сладкое">Fortified Sweet Fruit Wine</td><td>16.0%</td><td>160 g/dm³</td><td>18 months</td><td>0.5L / 0.7L</td></tr>
                        <tr><td>Antal</td><td data-en="Fortified Strong Fruit Wine" data-ru="Плодовое крепленое">Fortified Strong Fruit Wine</td><td>18.0%</td><td>50 g/dm³</td><td>12 months</td><td>0.5L / 0.7L</td></tr>
                        <tr><td>Backouskaye</td><td data-en="Table Semi-Sweet Fruit Wine" data-ru="Столовое полусладкое">Table Semi-Sweet Fruit Wine</td><td>11.0-12.9%</td><td>40 g/dm³</td><td>12 months</td><td>0.7L</td></tr>
                        <tr><td>Berry Quartet</td><td data-en="Table Semi-Sweet Fruit Wine" data-ru="Столовое полусладкое">Table Semi-Sweet Fruit Wine</td><td>11.0-12.9%</td><td>45 g/dm³</td><td>12 months</td><td>0.7L</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SLIDE 27: CIDERS & RTD COMPARISON TABLE -->
        <section class="slide" id="slide-27">
            <span class="section-tag" data-en="Ciders & RTD Comparison" data-ru="Сравнение сидров и напитков">Ciders & RTD Comparison</span>
            <h2 class="section-header" data-en="Ciders & Carbonated Drinks Specification Matrix" data-ru="Сравнительная таблица сидров и напитков">Ciders & Carbonated Drinks Specification Matrix</h2>
            <div class="clean-table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th data-en="Product Name" data-ru="Наименование">Product Name</th>
                            <th data-en="Category" data-ru="Категория">Category</th>
                            <th data-en="ABV" data-ru="Крепость">ABV</th>
                            <th data-en="Sugar" data-ru="Сахар">Sugar</th>
                            <th data-en="Shelf Life" data-ru="Срок годности">Shelf Life</th>
                            <th data-en="Bottle Size" data-ru="Объем">Bottle Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Apple Boom Dry</td><td data-en="Sparkling Dry Cider" data-ru="Сидр сухой">Sparkling Dry Cider</td><td>5.7%</td><td>≤ 3.0 g/dm³</td><td>16 months</td><td>0.4L Glass</td></tr>
                        <tr><td>Apple Boom Semi-Sweet</td><td data-en="Sparkling Semi-Sweet Cider" data-ru="Сидр полусладкий">Sparkling Semi-Sweet Cider</td><td>5.7%</td><td>50.0 g/dm³</td><td>16 months</td><td>0.4L Glass</td></tr>
                        <tr><td>Chinazes Cherry & Cinnamon</td><td data-en="Low-Alcohol RTD" data-ru="Слабоалкогольный напиток">Low-Alcohol RTD</td><td>5.0%</td><td>70 g/dm³</td><td>12 months</td><td>0.4L Glass</td></tr>
                        <tr><td>Chinazes Pear & Honey</td><td data-en="Low-Alcohol RTD" data-ru="Слабоалкогольный напиток">Low-Alcohol RTD</td><td>5.0%</td><td>70 g/dm³</td><td>12 months</td><td>0.4L Glass</td></tr>
                        <tr><td>Chinazes Lemon & Honey</td><td data-en="Low-Alcohol RTD" data-ru="Слабоалкогольный напиток">Low-Alcohol RTD</td><td>5.0%</td><td>70 g/dm³</td><td>12 months</td><td>0.4L Glass</td></tr>
                        <tr><td>Zero Bianco</td><td data-en="Non-Alcoholic Drink" data-ru="Безалкогольный напиток">Non-Alcoholic Drink</td><td>0.0%</td><td>Not specified</td><td>12 months</td><td>0.75L Glass</td></tr>
                        <tr><td>Rosso Allegro</td><td data-en="Youth Wine Drink" data-ru="Винный напиток">Youth Wine Drink</td><td>Not specified</td><td>Not specified</td><td>12 months</td><td>0.75L Glass</td></tr>
                        <tr><td>Belloro</td><td data-en="Youth Wine Drink" data-ru="Винный напиток">Youth Wine Drink</td><td>Not specified</td><td>Not specified</td><td>12 months</td><td>0.75L Glass</td></tr>
                        <tr><td>Lumino</td><td data-en="Youth Rosé Drink" data-ru="Розовый напиток">Youth Rosé Drink</td><td>Not specified</td><td>Not specified</td><td>12 months</td><td>0.75L Glass</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SLIDE 28: STARCH COMPARISON TABLE -->
        <section class="slide" id="slide-28">
            <span class="section-tag" data-en="Starch Matrix" data-ru="Сравнение крахмалов">Starch Matrix</span>
            <h2 class="section-header" data-en="Potato Starch vs Oxidized Starch Comparison" data-ru="Сравнение видов картофельного крахмала">Potato Starch vs Oxidized Starch Comparison</h2>
            <div class="clean-table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th data-en="Feature / Spec" data-ru="Характеристика">Feature / Spec</th>
                            <th data-en="Native Potato Starch (GOST 7699-78)" data-ru="Крахмал картофельный ГОСТ 7699-78">Native Potato Starch (GOST 7699-78)</th>
                            <th data-en="Oxidized Starch Oxyamyl OPV (E 1404)" data-ru="Окисленный крахмал Оксиамил ОПВ (Е 1404)">Oxidized Starch Oxyamyl OPV (E 1404)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-en="Modification Type" data-ru="Тип модификации">Modification Type</td>
                            <td data-en="Native natural untreated starch" data-ru="Нативный натуральный крахмал">Native natural untreated starch</td>
                            <td data-en="Chemical oxidation (Food additive E 1404)" data-ru="Химическое окисление (Е 1404)">Chemical oxidation (Food additive E 1404)</td>
                        </tr>
                        <tr>
                            <td data-en="Gel Viscosity & Clarity" data-ru="Вязкость и прозрачность">Gel Viscosity & Clarity</td>
                            <td data-en="High viscosity paste, clear gel" data-ru="Высокая вязкость клейстера, прозрачный гель">High viscosity paste, clear gel</td>
                            <td data-en="Lower viscosity, high gelling capacity" data-ru="Пониженная вязкость, высокие желирующие свойства">Lower viscosity, high gelling capacity</td>
                        </tr>
                        <tr>
                            <td data-en="Primary Food Industry" data-ru="Пищевая промышленность">Primary Food Industry</td>
                            <td data-en="Confectionery, bakery, meat, dairy, sauces" data-ru="Кондитерские, мясные, хлебопекарные изделия">Confectionery, bakery, meat, dairy, sauces</td>
                            <td data-en="Ice cream, jelly products, Turkish delight" data-ru="Мороженое, желейные изделия, рахат-лукум">Ice cream, jelly products, Turkish delight</td>
                        </tr>
                        <tr>
                            <td data-en="Non-Food Technical Uses" data-ru="Техническое применение">Non-Food Technical Uses</td>
                            <td data-en="Textile sizing, paper manufacturing, dextrin" data-ru="Шлихтование тканей, бумажная пром., декстрин">Textile sizing, paper manufacturing, dextrin</td>
                            <td data-en="Paper coating, technical binders" data-ru="Покрытие бумаги, техническое связующее">Paper coating, technical binders</td>
                        </tr>
                        <tr>
                            <td data-en="Packaging Options" data-ru="Варианты фасовки">Packaging Options</td>
                            <td data-en="PP bags 40kg & Paper packets 500g" data-ru="Мешки 40 кг и пакеты 500 г">PP bags 40kg & Paper packets 500g</td>
                            <td data-en="PP bags with PE liner 40kg" data-ru="Мешки с ПЭ вкладышем 40 кг">PP bags with PE liner 40kg</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SLIDE 29: TECHNICAL DATA & STORAGE -->
        <section class="slide" id="slide-29">
            <span class="section-tag" data-en="Logistics & Storage" data-ru="Логистика и хранение">Logistics & Storage</span>
            <h2 class="section-header" data-en="Storage Conditions & Shelf Stability" data-ru="Условия хранения и гарантийные сроки">Storage Conditions & Shelf Stability</h2>
            <div class="grid-3col">
                <div class="info-card">
                    <h3 data-en="🍷 Wine & Vermouth Storage" data-ru="🍷 Хранение вин и вермутов">🍷 Wine & Vermouth Storage</h3>
                    <p data-en="Store in ventilated, dark premises free from foreign odors at temperatures between +5°C and +20°C. Relative humidity not exceeding 85%." data-ru="Хранить в сухих, вентилируемых помещениях без посторонних запахов при температуре от +5°С до +20°С и влажности не более 85%.">Store in ventilated, dark premises free from foreign odors at temperatures between +5°C and +20°C. Relative humidity not exceeding 85%.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🍏 Cider & RTD Storage" data-ru="🍏 Хранение сидров и напитков">🍏 Cider & RTD Storage</h3>
                    <p data-en="Store in dark dry facilities at temperatures between +2°C and +20°C. Protect from direct sunlight. Shelf life: 12 to 16 months." data-ru="Хранить в затемненных помещениях при температуре от +2°С до +20°С. Защищать от прямых солнечных лучей. Срок: 12-16 месяцев.">Store in dark dry facilities at temperatures between +2°C and +20°C. Protect from direct sunlight. Shelf life: 12 to 16 months.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🥔 Starch Packaging & Storage" data-ru="🥔 Хранение крахмала">🥔 Starch Packaging & Storage</h3>
                    <p data-en="Store in dry, clean warehouses at max 75% relative humidity. Transported in 40kg PP bags with inner PE liners or 500g retail packets. Shelf life: 24 months." data-ru="Хранить в сухих складских помещениях при относительной влажности воздуха не более 75%. Срок хранения: 24 месяца со дня изготовления.">Store in dry, clean warehouses at max 75% relative humidity. Transported in 40kg PP bags with inner PE liners or 500g retail packets. Shelf life: 24 months.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 30: QUALITY STANDARDS -->
        <section class="slide" id="slide-30">
            <span class="section-tag" data-en="Quality Control" data-ru="Контроль качества">Quality Control</span>
            <h2 class="section-header" data-en="Laboratory Testing & Certified Safety" data-ru="Лабораторный контроль и сертификация">Laboratory Testing & Certified Safety</h2>
            <div class="grid-2col">
                <div class="info-card">
                    <h3 data-en="🔬 In-House Production Laboratory" data-ru="🔬 Лаборатория контроля производства">🔬 In-House Production Laboratory</h3>
                    <p data-en="Head of Production Control Laboratory: Melnik Marina Anatolyevna. The laboratory enforces strict physical, chemical, and microbiological testing at every manufacturing stage." data-ru="Начальник лаборатории по контролю производства: Мельник Марина Анатольевна. Лаборатория обеспечивает многоступенчатый физико-химический и микробиологический контроль.">Head of Production Control Laboratory: Melnik Marina Anatolyevna. The laboratory enforces strict physical, chemical, and microbiological testing at every manufacturing stage.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🛡️ HACCP & ISO Standard Compliance" data-ru="🛡️ Стандарты НАССР и ISO">🛡️ HACCP & ISO Standard Compliance</h3>
                    <p data-en="Full compliance with STB ISO 9001-2015 and HACCP food safety management systems, preventing contamination and ensuring uniform batch quality." data-ru="Полное соответствие системам СТБ ISO 9001-2015 и НАССР гарантирует высокую безопасность и стабильность физико-химических показателей всей продукции.">Full compliance with STB ISO 9001-2015 and HACCP food safety management systems, preventing contamination and ensuring uniform batch quality.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 31: RAW MATERIALS AND ECOLOGY -->
        <section class="slide" id="slide-31">
            <span class="section-tag" data-en="Eco Sourcing" data-ru="Экологичное сырье">Eco Sourcing</span>
            <h2 class="section-header" data-en="Natural Belarusian Fruits & Berries" data-ru="Натуральное сырье Белорусского края">Natural Belarusian Fruits & Berries</h2>
            <p class="section-desc" data-en="Veselovo plant relies exclusively on eco-friendly raw ingredients supplied by domestic agricultural farms." data-ru="Основой всех вин, сидров и крахмалопродуктов является натуральное сырье белорусского происхождения.">Veselovo plant relies exclusively on eco-friendly raw ingredients supplied by domestic agricultural farms.</p>
            <div class="grid-4col">
                <div class="info-card">
                    <h3 data-en="🍏 Selected Apples" data-ru="🍏 Сочные яблоки">🍏 Selected Apples</h3>
                    <p data-en="Fresh apple harvest forming the natural base for wines, 'Apple Boom' ciders, and 'Chinazes' drinks." data-ru="Свежий урожай яблок, образующий натуральную основу для вин, сидров «Apple Boom» и напитков.">Fresh apple harvest forming the natural base for wines, 'Apple Boom' ciders, and 'Chinazes' drinks.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🫐 Forest Berries" data-ru="🫐 Дикорастущие ягоды">🫐 Forest Berries</h3>
                    <p data-en="Wild and farm-grown chokeberry, blackcurrant, blueberry, elderberry, cranberry, and lingonberry." data-ru="Черноплодная рябина, черная смородина, черника, бузина, клюква и брусника.">Wild and farm-grown chokeberry, blackcurrant, blueberry, elderberry, cranberry, and lingonberry.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🌿 Herbal Infusions" data-ru="🌿 Натуральные травы">🌿 Herbal Infusions</h3>
                    <p data-en="Aromatic herbs including wormwood, peppermint, calamus, cardamom, thyme, oregano, and coriander for vermouths." data-ru="Пряно-ароматические травы: полынь, мята, аир, кардамон, чабрец, душица и кориандр для вермутов.">Aromatic herbs including wormwood, peppermint, calamus, cardamom, thyme, oregano, and coriander for vermouths.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🥔 High-Starch Potatoes" data-ru="🥔 Отборный картофель">🥔 High-Starch Potatoes</h3>
                    <p data-en="High-starch agricultural potato cultivars processed under strict temperature controls." data-ru="Высококрахмалистые сорта продовольственного картофеля местной заготавливаемой базы.">High-starch agricultural potato cultivars processed under strict temperature controls.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 32: B2B DISTRIBUTION NETWORKS -->
        <section class="slide" id="slide-32">
            <span class="section-tag" data-en="Distribution Channels" data-ru="Сеть дистрибуции">Distribution Channels</span>
            <h2 class="section-header" data-en="Commercial Trade & Distribution Network" data-ru="Коммерческая сеть и дистрибуция">Commercial Trade & Distribution Network</h2>
            <div class="grid-2col">
                <div class="info-card">
                    <h3 data-en="🛒 Trade Company MINSK KRISTALL TRADE" data-ru="🛒 ТУП «ТОРГОВАЯ КОМПАНИЯ «МИНСК КРИСТАЛЛ ТРЕЙД»">🛒 Trade Company MINSK KRISTALL TRADE</h3>
                    <p data-en="Official official trading partner managing wholesale delivery across retail chains, hypermarkets, and regional distributors in Belarus and export markets." data-ru="Унитарное предприятие «ТОРГОВАЯ КОМПАНИЯ «МИНСК КРИСТАЛЛ ТРЕЙД» (alcotrade.by) — официальный оператор продаж.">Official official trading partner managing wholesale delivery across retail chains, hypermarkets, and regional distributors in Belarus and export markets.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🚚 B2B Supply & Wholesale Logistics" data-ru="🚚 Поставки и оптовая логистика">🚚 B2B Supply & Wholesale Logistics</h3>
                    <p data-en="Commercial Department Head: Melnikova Tatyana Vladimirovna (+375 29 615-42-09). Direct supply contracts for wholesale buyers and retail chains." data-ru="Начальник коммерческого отдела: Мельникова Татьяна Владимировна (+375 29 615-42-09). Прямые договоры поставок.">Commercial Department Head: Melnikova Tatyana Vladimirovna (+375 29 615-42-09). Direct supply contracts for wholesale buyers and retail chains.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 33: B2B COOPERATION ADVANTAGES -->
        <section class="slide" id="slide-33">
            <span class="section-tag" data-en="B2B Benefits" data-ru="Преимущества сотрудничества">B2B Benefits</span>
            <h2 class="section-header" data-en="Why Partner with OJSC 'Food Plant Veselovo'" data-ru="Почему выбирают ОАО «Пищевой комбинат „Веселово“»">Why Partner with OJSC 'Food Plant Veselovo'</h2>
            <div class="grid-3col">
                <div class="info-card">
                    <h3 data-en="💎 State Quality Guarantee" data-ru="💎 Гарантия качества">💎 State Quality Guarantee</h3>
                    <p data-en="100% official state manufacturer holding holding status under MINSK KRISTALL GROUP, guaranteeing compliance with GOST, ISO, and HACCP." data-ru="Государственное предприятие в составе крупнейшего алкогольного холдинга страны с гарантией всех ГОСТов.">100% official state manufacturer holding holding status under MINSK KRISTALL GROUP, guaranteeing compliance with GOST, ISO, and HACCP.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="📦 Diverse Product Mix" data-ru="📦 Широкая матрица">📦 Diverse Product Mix</h3>
                    <p data-en="Single supply source for fortified fruit wines, award-winning vermouths, trending ciders, low-alcohol RTDs, and industrial starch." data-ru="Единый поставщик плодово-ягодных вин, вермутов, популярного сидра, напитков и картофельного крахмала.">Single supply source for fortified fruit wines, award-winning vermouths, trending ciders, low-alcohol RTDs, and industrial starch.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🤝 Flexible B2B Terms" data-ru="🤝 Гибкие условия B2B">🤝 Flexible B2B Terms</h3>
                    <p data-en="Tailored pricing, reliable shipment schedules, customized packaging, and direct support from commercial leadership." data-ru="Индивидуальный подход к оптовым закупкам, четкие графики отгрузок и прямая поддержка коммерческой службы.">Tailored pricing, reliable shipment schedules, customized packaging, and direct support from commercial leadership.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 34: INDUSTRIAL & PRIVATE LABEL CAPABILITIES -->
        <section class="slide" id="slide-34">
            <span class="section-tag" data-en="Industrial Partnership" data-ru="Промышленное партнерство">Industrial Partnership</span>
            <h2 class="section-header" data-en="Industrial Supplies & Starch Bulk Deliveries" data-ru="Промпоставки и оптовые партии крахмала">Industrial Supplies & Starch Bulk Deliveries</h2>
            <div class="grid-2col">
                <div class="info-card">
                    <h3 data-en="🏭 Bulk Starch Supply for Food Plants" data-ru="🏭 Оптовые поставки крахмала">🏭 Bulk Starch Supply for Food Plants</h3>
                    <p data-en="Supply of Potato Starch GOST 7699-78 (Extra, Superior, 1st, 2nd) and Oxidized Starch E 1404 in 40kg polypropylene bags with polyethylene liners for food, pharmaceutical, and textile plants." data-ru="Поставки картофельного и окисленного крахмала в мешках по 40 кг для пищевых, фармацевтических и текстильных предприятий.">Supply of Potato Starch GOST 7699-78 (Extra, Superior, 1st, 2nd) and Oxidized Starch E 1404 in 40kg polypropylene bags with polyethylene liners for food, pharmaceutical, and textile plants.</p>
                </div>
                <div class="info-card">
                    <h3 data-en="🍇 Bulk Fruit Wine Material" data-ru="🍇 Виноматериалы налив">🍇 Bulk Fruit Wine Material</h3>
                    <p data-en="Production and wholesale supply of fermented-alcohol fruit juices and natural processed fruit wine bases for industrial winemaking." data-ru="Производство и оптовая реализация сброженно-спиртованных соков и натуральных виноматериалов налив.">Production and wholesale supply of fermented-alcohol fruit juices and natural processed fruit wine bases for industrial winemaking.</p>
                </div>
            </div>
        </section>

        <!-- SLIDE 35: MANAGEMENT & GOVERNANCE -->
        <section class="slide" id="slide-35">
            <span class="section-tag" data-en="Enterprise Management" data-ru="Руководство и структура">Enterprise Management</span>
            <h2 class="section-header" data-en="Executive Leadership & Key Department Heads" data-ru="Руководство предприятия">Executive Leadership & Key Department Heads</h2>
            <div class="clean-table-wrapper">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th data-en="Position" data-ru="Должность">Position</th>
                            <th data-en="Full Name" data-ru="ФИО руководителя">Full Name</th>
                            <th data-en="Direct Contact Phone" data-ru="Прямой телефон">Direct Contact Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-en="Director of OJSC Food Plant Veselovo" data-ru="Директор Общества">Director of OJSC Food Plant Veselovo</td>
                            <td>Luzko Oleg Nikolayevich</td>
                            <td>+375 (177) 93-34-08</td>
                        </tr>
                        <tr>
                            <td data-en="Chief Accountant" data-ru="Главный бухгалтер">Chief Accountant</td>
                            <td>Sheremetyeva Natalya Vladimirovna</td>
                            <td>+375 (177) 93-34-33</td>
                        </tr>
                        <tr>
                            <td data-en="Chief Engineer" data-ru="Главный инженер">Chief Engineer</td>
                            <td>Kurilenko Mikhail Ivanovich</td>
                            <td>+375 (177) 93-34-38</td>
                        </tr>
                        <tr>
                            <td data-en="Chief Economist" data-ru="Главный экономист">Chief Economist</td>
                            <td>Maslovskaya Natalya Konstantinovna</td>
                            <td>+375 (177) 93-34-11</td>
                        </tr>
                        <tr>
                            <td data-en="Commercial Department Head" data-ru="Начальник коммерческого отдела">Commercial Department Head</td>
                            <td>Melnikova Tatyana Vladimirovna</td>
                            <td>+375 (177) 78-78-41 / +375 29 615-42-09</td>
                        </tr>
                        <tr>
                            <td data-en="Chief Technologist" data-ru="Главный технолог">Chief Technologist</td>
                            <td>Gakut Inna Maryanovna</td>
                            <td>+375 (177) 93-34-36</td>
                        </tr>
                        <tr>
                            <td data-en="Head of Production" data-ru="Начальник производства">Head of Production</td>
                            <td>Tolkach Anna Vyacheslavovna</td>
                            <td>+375 (177) 93-34-05</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SLIDE 36: CONTACTS & PARTNERSHIP -->
        <section class="slide" id="slide-36">
            <div class="grid-2col" style="align-items: center;">
                <div>
                    <span class="section-tag" data-en="Official Contacts" data-ru="Официальные контакты">Official Contacts</span>
                    <h2 class="section-header" data-en="OJSC 'Food Plant Veselovo'" data-ru="ОАО «Пищевой комбинат „Веселово“»">OJSC 'Food Plant Veselovo'</h2>
                    <p class="section-desc" data-en="We welcome international partners, distributors, and wholesale buyers to discuss commercial partnership." data-ru="Приглашаем к сотрудничеству международных партнеров, дистрибьюторов и оптовых покупателей.">We welcome international partners, distributors, and wholesale buyers to discuss commercial partnership.</p>

                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: var(--text-secondary);">
                        <div>📍 <strong data-en="Address:" data-ru="Адрес:">Address:</strong> 222132, Republic of Belarus, Minsk region, Borisov district, Veselovo village, Zavodskaya str. 24</div>
                        <div>📞 <strong data-en="Reception:" data-ru="Приемная:">Reception:</strong> +375 (177) 93-34-08 | <strong data-en="Fax:" data-ru="Факс:">Fax:</strong> +375 (177) 78-78-40</div>
                        <div>📈 <strong data-en="Sales Dept:" data-ru="Отдел сбыта:">Sales Dept:</strong> +375 (177) 93-34-32, +375 (177) 93-34-37, +375 29 615-42-09</div>
                        <div>✉️ <strong data-en="Email:" data-ru="E-mail:">Email:</strong> <a href="mailto:info@alco.by" style="color: var(--accent-green); text-decoration: none;">info@alco.by</a></div>
                        <div>🌐 <strong data-en="Official Catalog Web:" data-ru="Официальный каталог:">Official Catalog Web:</strong> <a href="https://alco.by/instal/productia/" target="_blank" style="color: var(--accent-green); font-weight: 600;">https://alco.by/instal/productia/</a></div>
                    </div>
                </div>

                <div class="info-card" style="text-align: center; padding: 40px;">
                    <img src="https://alco.by/instal/wp-content/uploads/2026/01/200-3.png" alt="Veselovo Logo" style="height: 70px; object-fit: contain; margin-bottom: 20px;">
                    <h3 data-en="MINSK KRISTALL GROUP" data-ru="ХОЛДИНГ «МИНСК КРИСТАЛЛ ГРУПП»">MINSK KRISTALL GROUP</h3>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;" data-en="Managing Company: OJSC MINSK KRISTALL<br>Minsk, Oktyabrskaya str. 15<br>Trade Operator: MINSK KRISTALL TRADE (alcotrade.by)" data-ru="Управляющая компания: ОАО «МИНСК КРИСТАЛЛ»<br>г. Минск, ул. Октябрьская, 15<br>Торговый оператор: «МИНСК КРИСТАЛЛ ТРЕЙД» (alcotrade.by)">Managing Company: OJSC MINSK KRISTALL<br>Minsk, Oktyabrskaya str. 15<br>Trade Operator: MINSK KRISTALL TRADE (alcotrade.by)</p>
                    <div style="font-size: 12px; color: var(--text-muted);" data-en="© OJSC Food Plant Veselovo. All rights reserved." data-ru="© ОАО «Пищевой комбинат „Веселово“». Все права защищены.">© OJSC Food Plant Veselovo. All rights reserved.</div>
                </div>
            </div>
        </section>

    </main>

    <script>
        let currentLanguage = 'en';
        let currentSlideIndex = 1;
        const totalSlides = 36;
        let currentMode = 'slides';

        function switchLanguage(lang) {{
            currentLanguage = lang;
            document.getElementById('btn-en').classList.toggle('active', lang === 'en');
            document.getElementById('btn-ru').classList.toggle('active', lang === 'ru');

            document.querySelectorAll('[data-en][data-ru]').forEach(el => {{
                const text = el.getAttribute(`data-${{lang}}`);
                if (text) {{
                    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {{
                        el.value = text;
                    }} else {{
                        el.innerHTML = text;
                    }}
                }}
            }});
        }}

        function setViewMode(mode) {{
            currentMode = mode;
            document.body.className = mode === 'slides' ? 'mode-slides' : 'mode-scroll';
            document.getElementById('mode-slides-btn').classList.toggle('active', mode === 'slides');
            document.getElementById('mode-scroll-btn').classList.toggle('active', mode === 'scroll');

            if (mode === 'slides') {{
                showSlide(currentSlideIndex);
            }}
        }}

        function showSlide(index) {{
            if (index < 1) index = 1;
            if (index > totalSlides) index = totalSlides;
            currentSlideIndex = index;

            document.querySelectorAll('.slide').forEach((slide, idx) => {{
                slide.classList.toggle('active-slide', idx + 1 === currentSlideIndex);
            }});

            document.getElementById('slideCounter').innerText = `SLIDE ${{currentSlideIndex}} / ${{totalSlides}}`;

            if (currentMode === 'scroll') {{
                const targetSlide = document.getElementById(`slide-${{currentSlideIndex}}`);
                if (targetSlide) {{
                    targetSlide.scrollIntoView({{ behavior: 'smooth' }});
                }}
            }}
        }}

        function prevSlide() {{ showSlide(currentSlideIndex - 1); }}
        function nextSlide() {{ showSlide(currentSlideIndex + 1); }}

        function toggleFullscreen() {{
            if (!document.fullscreenElement) {{
                document.documentElement.requestFullscreen().catch(err => {{}});
            }} else {{
                if (document.exitFullscreen) {{
                    document.exitFullscreen();
                }}
            }}
        }}

        document.addEventListener('keydown', (e) => {{
            if (e.key === 'ArrowRight' || e.key === 'Space') {{
                e.preventDefault();
                nextSlide();
            }} else if (e.key === 'ArrowLeft') {{
                e.preventDefault();
                prevSlide();
            }} else if (e.key === 'f' || e.key === 'F') {{
                toggleFullscreen();
            }} else if (e.key === 'Home') {{
                showSlide(1);
            }} else if (e.key === 'End') {{
                showSlide(totalSlides);
            }}
        }});

        showSlide(1);
    </script>
</body>
</html>
'''

with open("veselovo_product_portfolio.html", "w", encoding="utf-8") as f:
    f.write(full_document)

print("Clean document generated successfully!")
