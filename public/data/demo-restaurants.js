window.DEMO_RESTAURANTS = [
  {
    id: 'table-verte',
    name: 'La Table Verte',
    city: 'Metz',
    neighborhood: 'Centre',
    cuisines: ['Végétarien', 'Cuisine française'],
    offers: [
      {
        platformId: 'uber-eats', platformName: 'Uber Eats', fulfillmentMode: 'delivery', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 24.80, deliveryFeeExample: 2.90, serviceFeeExample: null,
        etaMinutes: [25, 35], minimumOrderExample: null, fictitious: true
      },
      {
        platformId: 'uber-eats', platformName: 'Uber Eats', fulfillmentMode: 'pickup', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 24.80, deliveryFeeExample: 0, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      },
      {
        platformId: 'deliveroo', platformName: 'Deliveroo', fulfillmentMode: 'delivery', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 23.90, deliveryFeeExample: 3.20, serviceFeeExample: null,
        etaMinutes: [30, 40], minimumOrderExample: null, fictitious: true
      },
      {
        platformId: 'deliveroo', platformName: 'Deliveroo', fulfillmentMode: 'pickup', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 23.90, deliveryFeeExample: 0, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      }
    ]
  },
  {
    id: 'burger-atelier',
    name: 'Burger Atelier',
    city: 'Metz',
    neighborhood: 'Sablon',
    cuisines: ['Burgers', 'Street food'],
    offers: [
      {
        platformId: 'uber-eats', platformName: 'Uber Eats', fulfillmentMode: 'delivery', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 19.40, deliveryFeeExample: null, serviceFeeExample: null,
        etaMinutes: [20, 30], minimumOrderExample: 12, fictitious: true
      },
      {
        platformId: 'uber-eats', platformName: 'Uber Eats', fulfillmentMode: 'pickup', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 19.40, deliveryFeeExample: 0, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      }
    ]
  },
  {
    id: 'maison-nori',
    name: 'Maison Nori',
    city: 'Montigny-lès-Metz',
    neighborhood: 'Centre',
    cuisines: ['Japonais', 'Sushi'],
    offers: [
      {
        platformId: 'deliveroo', platformName: 'Deliveroo', fulfillmentMode: 'delivery', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 27.10, deliveryFeeExample: 1.90, serviceFeeExample: null,
        etaMinutes: [30, 40], minimumOrderExample: null, fictitious: true
      },
      {
        platformId: 'deliveroo', platformName: 'Deliveroo', fulfillmentMode: 'pickup', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 27.10, deliveryFeeExample: 0, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      }
    ]
  },
  {
    id: 'curry-jardin',
    name: 'Curry du Jardin',
    city: 'Metz',
    neighborhood: 'Nouvelle Ville',
    cuisines: ['Indien', 'Végétarien'],
    offers: [
      {
        platformId: 'uber-eats', platformName: 'Uber Eats', fulfillmentMode: 'delivery', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 21.60, deliveryFeeExample: 2.50, serviceFeeExample: null,
        etaMinutes: [25, 45], minimumOrderExample: null, fictitious: true
      },
      {
        platformId: 'deliveroo', platformName: 'Deliveroo', fulfillmentMode: 'pickup', categories: ['meal'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: null, deliveryFeeExample: null, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      }
    ]
  },
  {
    id: 'epicerie-sablon-demo',
    name: 'Épicerie du Sablon',
    city: 'Metz',
    neighborhood: 'Sablon',
    cuisines: ['Courses', 'Épicerie', 'Produits du quotidien'],
    offers: [
      {
        platformId: 'uber-eats', platformName: 'Uber Eats', fulfillmentMode: 'delivery', categories: ['grocery'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 31.50, deliveryFeeExample: 2.90, serviceFeeExample: null,
        etaMinutes: [20, 35], minimumOrderExample: null, fictitious: true
      },
      {
        platformId: 'deliveroo', platformName: 'Deliveroo', fulfillmentMode: 'delivery', categories: ['grocery'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 31.50, deliveryFeeExample: 3.10, serviceFeeExample: null,
        etaMinutes: [20, 35], minimumOrderExample: null, fictitious: true
      }
    ]
  },
  {
    id: 'panier-anti-gaspi-demo',
    name: 'Panier anti-gaspi du Marché',
    city: 'Metz',
    neighborhood: 'Centre',
    cuisines: ['Anti-gaspi', 'Panier surprise', 'Retrait'],
    offers: [
      {
        platformId: 'too-good-to-go', platformName: 'Too Good To Go', fulfillmentMode: 'pickup', categories: ['anti-waste', 'grocery'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 4.99, deliveryFeeExample: 0, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      }
    ]
  },
  {
    id: 'courses-consignees-demo',
    name: 'Panier de courses consignées',
    city: 'Zone fictive',
    neighborhood: 'Démonstration',
    cuisines: ['Courses', 'Consigne', 'Épicerie'],
    offers: [
      {
        platformId: 'le-fourgon', platformName: 'Le Fourgon', fulfillmentMode: 'delivery', categories: ['grocery'], officialUrl: null,
        dataState: 'demo', source: 'Fixture locale de démonstration', verifiedAt: null,
        itemPriceExample: 42.00, deliveryFeeExample: null, serviceFeeExample: null,
        etaMinutes: null, minimumOrderExample: null, fictitious: true
      }
    ]
  }
];
