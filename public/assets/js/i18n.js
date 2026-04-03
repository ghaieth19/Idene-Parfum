/* ═══════════════════════════════════════════════════════════════
   IDENE PARFUM — Internationalization (i18n) System
   Supports: fr (French, default) ↔ ar (Arabic, RTL)
   ═══════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  // ── Translation dictionaries ──────────────────────────────
  const translations = {
    ar: {
      // ── Navbar ──
      'nav.advantages': 'المزايا',
      'nav.catalog': 'الكتالوج',
      'nav.howToOrder': 'كيفية الطلب',
      'nav.contact': 'اتصل بنا',
      'nav.login': 'تسجيل الدخول',
      'nav.proAccount': 'حساب مهني',
      'nav.mySpace': 'حسابي',

      // ── Hero ──
      'hero.badge': 'منصة B2B · مباشر من المصنع',
      'hero.title': 'IDENE — زيت العطر <em>الأقرب إلى المصدر</em>',
      'hero.subtitle': 'تزويد B2B مباشر من المصنع لمحلات العطور المهنية. اطلب زيوت العطور بالجملة بأفضل الأسعار.',
      'hero.viewCatalog': 'عرض الكتالوج',
      'hero.requestPro': 'طلب حساب مهني',
      'hero.perfumesAvailable': 'عطر متوفر',
      'hero.directDelivery': 'توصيل مباشر',
      'hero.qualityControlled': 'جودة مضمونة',

      // ── Essence Section ──
      'essence.kicker': 'فن الإبداع',
      'essence.title': 'جوهر نقي، نابض، خالد.',
      'essence.text': 'اكتشف مجموعتنا الحصرية من زيوت العطور. كل قطرة هي نتيجة اختيار دقيق من المصدر لجودة لا تضاهى.',
      'essence.cta': 'اكتشف العملية',

      // ── Why IDENE ──
      'why.kicker': 'مزايانا',
      'why.title': 'لماذا تختار IDENE',
      'why.subtitle': 'حل شامل مصمم لمحترفي العطور',
      'why.card1.title': 'تزويد موثوق',
      'why.card1.text': 'قاعدة عطور منظمة حسب الفئة والشريحة. تابع التوفر في الوقت الفعلي واطلب بكل ثقة.',
      'why.card2.title': 'توصيل مباشر من المصنع',
      'why.card2.text': 'أفضل الأسعار مضمونة، جودة مراقبة من المصدر. شحن خلال 48 ساعة لجميع الطلبات المؤكدة.',
      'why.card3.title': 'حساب مهني مخصص',
      'why.card3.text': 'أسعار الجملة والتفصيل المخصصة، سجل كامل للطلبات، فواتير PDF ومتابعة الدفع.',

      // ── Catalog Preview ──
      'catalog.kicker': 'كتالوجنا',
      'catalog.title': 'معاينة عطورنا',
      'catalog.text': 'اكتشف مقتطفًا من مجموعتنا. سجّل الدخول لرؤية جميع الأسعار وتقديم الطلبات.',
      'catalog.viewAll': 'عرض الكتالوج الكامل',
      'catalog.loading': 'جاري تحميل المنتجات...',
      'catalog.noProducts': 'لا توجد منتجات متوفرة.',
      'catalog.error': 'خطأ في التحميل.',

      // ── Full catalog page ──
      'catalogPage.kicker': 'الكتالوج الكامل',
      'catalogPage.title': 'عطورنا المهنية',
      'catalogPage.text': 'استكشف مجموعتنا الكاملة. سجّل الدخول لرؤية الأسعار وتقديم الطلبات.',
      'catalogPage.search': 'ابحث عن عطر...',
      'catalogPage.allStock': 'جميع المخزون',
      'catalogPage.inStock': 'متوفر',
      'catalogPage.limited': 'مخزون محدود',
      'catalogPage.outOfStock': 'نفذ',

      // ── Steps ──
      'steps.kicker': 'دليل الاستخدام',
      'steps.title': 'كيفية الطلب',
      'steps.subtitle': 'أربع خطوات بسيطة لاستلام عطورك',
      'steps.step1.title': 'أنشئ حسابك المهني',
      'steps.step1.text': 'تسجيل سريع مع التحقق من حالتك المهنية من قبل فريقنا.',
      'steps.step2.title': 'اختر عطورك',
      'steps.step2.text': 'ابحث حسب الفئة والشريحة والتوفر. اطلع على الأسعار والمخزون في الوقت الفعلي.',
      'steps.step3.title': 'قدّم طلبك',
      'steps.step3.text': 'أضف الكميات المطلوبة إلى السلة وأكد طلبك بنقرة واحدة.',
      'steps.step4.title': 'استلم وادفع',
      'steps.step4.text': 'توصيل خلال 48 ساعة إلى محل العطور الخاص بك. فاتورة PDF تُنشأ تلقائياً.',

      // ── CTA ──
      'cta.title': 'انضم إلى محلات العطور التي تثق بـ IDENE',
      'cta.text': 'وصول إلى منصتنا B2B وسهّل تزويدك بدءاً من اليوم',
      'cta.button': 'الحصول على حسابي المهني',
      'cta.buttonLoggedIn': 'الوصول إلى حسابي',
      'cta.ready': 'مستعد للطلب؟',
      'cta.readyText': 'أنشئ حسابك المهني واحصل على الأسعار والمخزون في الوقت الفعلي والطلب عبر الإنترنت.',
      'cta.createPro': 'إنشاء حسابي المهني',

      // ── Footer ──
      'footer.brand': 'IDENE PARFUM',
      'footer.description': 'زيت العطر الأقرب إلى المصدر. تزويد B2B مباشر من المصنع لمحلات العطور المهنية.',
      'footer.proOnly': 'حصري للمهنيين',
      'footer.navigation': 'التنقل',
      'footer.home': 'الرئيسية',
      'footer.information': 'معلومات',
      'footer.about': 'من نحن',
      'footer.terms': 'الشروط العامة',
      'footer.privacy': 'سياسة الخصوصية',
      'footer.contactTitle': 'اتصل بنا',
      'footer.rights': '© 2025 IDENE PARFUM. جميع الحقوق محفوظة. منصة حصرية للمهنيين.',

      // ── Auth Page ──
      'auth.buyBulk': 'شراء بالجملة',
      'auth.heroTitle': 'زيت العطر<br>الأقرب إلى المصدر.',
      'auth.heroText': 'أنشئ حسابك B2B للوصول الفوري إلى الأسعار المهنية والطلبات المباشرة من المصنع.',
      'auth.selection': 'المجموعة',
      'auth.selectionDetail': '+200 عطر في المخزون',
      'auth.selectionText': 'متوفر بأحجام 250 مل، 500 مل و1 لتر لتلبية احتياجاتك.',
      'auth.qualityService': 'الجودة والخدمة',
      'auth.qualityDetail': 'شحن ذو أولوية خلال 48 ساعة',
      'auth.qualityText': 'اطلب ببضع نقرات، تتبع توصيلك في الوقت الفعلي.',
      'auth.loginTab': 'تسجيل الدخول',
      'auth.signupTab': 'إنشاء حساب',
      'auth.welcome': 'مرحباً',
      'auth.accessSpace': 'ادخل إلى حسابك',
      'auth.email': 'البريد الإلكتروني',
      'auth.password': 'كلمة المرور',
      'auth.forgotPassword': 'نسيت كلمة المرور؟',
      'auth.loginBtn': 'تسجيل الدخول',
      'auth.or': 'أو',
      'auth.faceLogin': 'تسجيل الدخول بالوجه',
      'auth.newClient': 'عميل جديد',
      'auth.createPro': 'إنشاء حساب مهني',
      'auth.lastName': 'اللقب',
      'auth.firstName': 'الاسم',
      'auth.shopName': 'اسم المحل',
      'auth.phone': 'الهاتف',
      'auth.location': 'المدينة / الموقع',
      'auth.proEmail': 'البريد الإلكتروني المهني',
      'auth.createPassword': 'إنشاء كلمة مرور',
      'auth.enableFace': 'تفعيل التعرف على الوجه لعمليات تسجيل الدخول القادمة',
      'auth.biometric': 'التسجيل البيومتري',
      'auth.biometricText': 'التقط وجهك مرة واحدة لتسريع عمليات تسجيل الدخول المستقبلية.',
      'auth.openCamera': 'فتح الكاميرا',
      'auth.validateFace': 'تأكيد الوجه',
      'auth.signupBtn': 'التسجيل',
      'auth.legal': 'بالاستمرار، أنت توافق على شروط الاستخدام وسياسة الخصوصية.',
      'auth.faceScan': 'مسح الوجه',
      'auth.facePosition': 'ضع وجهك في الإطار ثم التقط.',
      'auth.capture': 'التقاط',
      'auth.cancel': 'إلغاء',
      'auth.contactIdene': 'اتصل بـ Idene Parfum',
      'auth.backToLogin': 'العودة لتسجيل الدخول',

      // ── Product cards ──
      'product.homme': 'رجالي',
      'product.femme': 'نسائي',
      'product.mixte': 'مزدوج',
      'product.enfant': 'أطفال',
      'product.inStock': '✅ متوفر',
      'product.limited': '⚠️ مخزون محدود',
      'product.outOfStock': '🔴 نفذ',
      'product.loginPrice': '🔒 سجّل الدخول لرؤية السعر',
      'product.loginOrder': 'سجّل الدخول للطلب',

      // ── Dashboard ──
      'dash.shop': 'المتجر',
      'shop.cart': 'السلة',
      'dash.invoices': 'الفواتير',
      'dash.dashboard': 'لوحة التحكم',
      'dash.orders': 'طلباتي',
      'dash.myInvoices': 'فواتيري',
      'dash.settings': 'الإعدادات',
      'dash.logout': 'تسجيل الخروج',
      'dash.orderHistory': 'سجل الطلبات',
      'dash.myAccount': 'حسابي',
      'dash.search': 'بحث...',
      'dash.products': 'منتجات في الكتالوج',
      'dash.categories': 'فئات',
      'dash.inStock': 'متوفر',
      'dash.ofCatalog': 'من الكتالوج',
      'dash.limitedStock': 'كمية محدودة',
      'dash.outOfStock': 'نفذت الكمية',
      'dash.noOrders': 'لا توجد طلبات.',
      'dash.number': 'الرقم',
      'dash.amount': 'المبلغ',
      'dash.status': 'الحالة',
      'dash.noSales': 'لا توجد مبيعات متاحة حالياً.',
      'dash.sales': 'مبيعات',
      'dash.ordersMin': 'طلبات',
      'dash.noMatch': 'لا يوجد عطر يطابق معاييرك.',
      'dash.add': 'إضافة',
      'dash.added': 'تمت الإضافة !',
      'dash.noOrdersFound': 'لم يتم العثور على أي طلب.',
      'dash.orderNum': 'رقم الطلب',
      'dash.date': 'التاريخ',
      'dash.action': 'إجراء',
      'dash.edit': 'تعديل',
      'dash.delete': 'حذف',
      'dash.validatedTimeout': 'تم التحقق / تجاوز 24 ساعة',
      'dash.noInvoicesFound': 'لم يتم العثور على فواتير.',
      'dash.invoice': 'الفاتورة',
      'dash.issueDate': 'تاريخ الإصدار',
      'dash.name': 'الاسم',
      'dash.paymentStatus': 'حالة الدفع',

      // ── Checkout & Cart ──
      'checkout.address': 'عنوان التوصيل',
      'checkout.addrLine1': 'العنوان *',
      'checkout.phone': 'رقم الهاتف',
      'checkout.city': 'المدينة *',
      'checkout.region': 'الولاية',
      'checkout.country': 'البلد',
      'checkout.deliveryAddress': 'عنوان التوصيل',
      'checkout.summary': 'ملخص',
      'checkout.confirmBtn': 'تأكيد الطلب',
      'checkout.continueShopping': 'مواصلة التسوق',
      'checkout.thankYou': 'شكرا لطلبك!',
      'checkout.successMsg': 'تم تسجيل طلبك بنجاح.',
      'checkout.newOrder': 'طلب جديد',
      'checkout.viewOrders': 'عرض طلباتي',
      'cart.title': 'سلتي',
      'cart.empty': 'سلتك فارغة',
      'cart.total': 'المجموع',
      'cart.checkoutBtn': 'تأكيد الطلب',
      'cart.clearBtn': 'إفراغ السلة',
      'dash.dashboardTitle': 'لوحة التحكم',
      'dash.welcome': 'مرحباً',
      'dash.kpiOrders': 'الطلبات',
      'dash.kpiTotal': 'المبلغ الإجمالي',
      'dash.kpiUnpaid': 'غير مدفوع',
      'dash.kpiMonth': 'هذا الشهر',
      'dash.productStats': 'إحصائيات المنتجات',
      'dash.bySegment': 'حسب الفئة',
      'dash.recentOrders': 'الطلبات الأخيرة',
      'dash.topPerfumes': 'أفضل 10 عطور مبيعاً',
      'shop.noProducts': 'لا يوجد منتج',
      'shop.add': 'إضافة',
      'shop.outOfStockBtn': 'نفذت',

      // ── Admin ──
      'admin.uxTitle': 'إدارة تجربة المستخدم',
      'admin.pilotBoard': 'لوحة القيادة',
      'admin.globalVision': 'رؤية شاملة',
      'admin.quickActions': 'إجراءات سريعة',
      'admin.proDesign': 'تصميم احترافي',
      'admin.financialOps': 'الإدارة المالية والتشغيلية',
      'admin.clearDashboard': 'لوحة إدارة أوضح وأكثر احترافية',
      'admin.leadText': 'إيرادات اليوم، رقم الشهر، المصاريف، مشتريات المواد الأولية، المخزون والربح المقدر في عرض أكثر وضوحاً وسهولة في القراءة.',
      'admin.newVisual': 'اتجاه بصري جديد',
      'admin.fullFlux': 'تدفق إداري كامل',
      'admin.organizedInterface': 'واجهة منظمة لمتابعة الطلبات والربحية والعمليات بدون عبء بصري.',
      'admin.recentOrders': 'الطلبات الأخيرة',
      'admin.orderActivity': 'نشاط الطلبات',
      'admin.navTitle': 'التنقل',
      'admin.administration': 'الإدارة',
      'admin.centralMgmt': 'الإدارة المركزية',
      'admin.overview': 'نظرة عامة',
      'admin.myAccount': 'حسابي',
      'admin.products': 'المنتجات',
      'admin.materialStock': 'مخزون المواد',
      'admin.documents': 'الوثائق',
      'admin.employees': 'الموظفين',
      'admin.expenses': 'المصاريف',
      'admin.clientView': 'عرض العميل',
      'admin.productDesc': 'قائمة المنتجات الكاملة وإدارة الكتالوج.',
      'admin.addProduct': 'إضافة منتج',
      'admin.reference': 'المرجع',
      'admin.name': 'الاسم',
      'admin.range': 'الفئة',
      'admin.segment': 'الشريحة',
      'admin.stock': 'المخزون',
      'admin.buyPrice': 'سعر الشراء',
      'admin.sellPrice': 'سعر البيع',
      'admin.actions': 'الإجراءات',
      'admin.newProduct': 'منتج جديد',
      'admin.perfumeName': 'اسم العطر *',
      'admin.save': 'حفظ',
      'admin.cancel': 'إلغاء',
      'admin.ordersDesc': 'إدارة ومتابعة طلبات العملاء.',
      'admin.newOrder': 'طلب جديد',
      'admin.number': 'الرقم',
      'admin.date': 'التاريخ',
      'admin.client': 'العميل',
      'admin.amount': 'المبلغ',
      'admin.status': 'الحالة',
      'admin.payment': 'الدفع',
      'admin.usersDesc': 'إدارة حسابات العملاء والصلاحيات.',
      'admin.email': 'البريد الإلكتروني',
      'admin.phone': 'الهاتف',
      'admin.shop': 'المتجر',
      'admin.docsDesc': 'الفواتير والوثائق المولدة من النظام.',
      'admin.document': 'الوثيقة',
      'admin.type': 'النوع',
      'admin.generatedOn': 'تاريخ التوليد',
      'admin.employeesDesc': 'إدارة حسابات الموظفين والصلاحيات.',
      'admin.addEmployee': 'إضافة موظف',
      'admin.employee': 'الموظف',
      'admin.role': 'الدور',
      'admin.expensesDesc': 'متابعة المصاريف والتكاليف التشغيلية.',
      'admin.addExpense': 'إضافة مصروف',
      'admin.label': 'البيان',
      'admin.materialStockDesc': 'إدارة المواد الأولية ومستويات المخزون.',
      'admin.accountDesc': 'معلومات الحساب والأمان.',
      'admin.todayRevenue': 'إيرادات اليوم',
      'admin.monthRevenue': 'إيرادات الشهر',
      'admin.monthExpenses': 'مصاريف الشهر',
      'admin.monthMaterials': 'مشتريات المواد',
      'admin.salaries': 'الرواتب',
      'admin.estimatedProfit': 'الربح المقدر',
      'admin.stockValue': 'قيمة المخزون',
      'admin.outOfStock': 'نفذ من المخزون',
      'admin.openOrders': 'الطلبات المفتوحة',
      'admin.materialAlerts': 'تنبيهات المواد',
      'admin.choosePerfume': 'اختر عطراً أو نوعاً سريعاً',
      'admin.chooseProduct': 'اختر منتجاً',
      'admin.invalidQty': 'الكمية غير صالحة',
      'admin.invalidPrice': 'السعر غير صالح',
      'admin.productAdded': 'تمت إضافة المنتج إلى سلة الإدارة',
      'admin.invoicePrefilled': 'تم تعبئة الفاتورة مسبقاً من أمر الشراء',
      'admin.orderPrefilled': 'تم تعبئة أمر الشراء مسبقاً من الفاتورة',
      'admin.productRemoved': 'تم إزالة المنتج من سلة الإدارة',
      'admin.orderModified': 'تم تعديل الطلب',
      'admin.choosePerfumery': 'اختر عطراً',
      'admin.addAtLeastOne': 'أضف منتجاً واحداً على الأقل',
      'admin.invoiceSaved': 'تم حفظ فاتورة الإدارة',
      'admin.orderSaved': 'تم حفظ أمر الشراء',
      'admin.productSaved': 'تم حفظ المنتج',
      'admin.employeeAdded': 'تمت إضافة الموظف',
      'admin.userProfileUpdated': 'تم تحديث ملف المستخدم',
      'admin.validating...': 'جاري التحقق...',
      'admin.validatePayment': 'تأكيد الدفع',
      'admin.jobTitle': 'المنصب',
      'admin.salaryDT': 'الراتب DT',
      'admin.statusActiveInactiveSuspended': 'الحالة نشط / غير نشط / معلق',
      'admin.newUser': 'مستخدم جديد',
      'admin.editUser': 'تعديل المستخدم',
      'admin.deleteUser': 'حذف المستخدم',
      'admin.confirmDelete': 'هل أنت متأكد من الحذف؟',
      'admin.validateOrder': 'تأكيد الطلب',
      'admin.cancelOrder': 'إلغاء الطلب',
      'admin.generateInvoice': 'إنشاء فاتورة',
      'admin.generatePurchaseOrder': 'إنشاء أمر شراء',
      'admin.exportPdf': 'تصدير PDF',
      'admin.applyPayment': 'تطبيق الدفع',
      'admin.close': 'إغلاق',
      'admin.saveChanges': 'حفظ التغييرات',
      'admin.searchPlaceholder': 'بحث...',
      'admin.all': 'الكل',
      'admin.confirmed': 'مؤكد',
      'admin.preparing': 'قيد التحضير',
      'admin.shipped': 'تم الشحن',
      'admin.delivered': 'تم التسليم',
      'admin.cancelled': 'ملغي',
      'admin.paid': 'مدفوع',
      'admin.partial': 'جزئي',
      'admin.unpaid': 'غير مدفوع',
      'admin.pending': 'قيد الانتظار',
      'admin.active': 'نشط',
      'admin.inactive': 'غير نشط',
      'admin.suspended': 'معلق',
      'admin.productCode': 'رمز المنتج',
      'admin.productName': 'اسم المنتج',
      'admin.productPrice': 'سعر المنتج',
      'admin.productStock': 'مخزون المنتج',
      'admin.alertThreshold': 'حد التنبيه',
      'admin.rawMaterialStock': 'مخزون المواد الأولية',
      'admin.rawMaterialAlert': 'تنبيه المواد الأولية',
      'admin.sku': 'رمز SKU',
      'admin.barcode': 'الرمز الشريطي',
      'admin.activeProduct': 'منتج نشط',
      'admin.materialCategory': 'فئة المادة',
      'admin.supplier': 'المورد',
      'admin.unitCost': 'تكلفة الوحدة',
      'admin.quantity': 'الكمية',
      'admin.unit': 'الوحدة',
      'admin.purchaseDate': 'تاريخ الشراء',
      'admin.notes': 'ملاحظات',
      'admin.orderNumber': 'رقم الطلب',
      'admin.orderDate': 'تاريخ الطلب',
      'admin.deliveryAddress': 'عنوان التوصيل',
      'admin.contactPerson': 'الشخص المسؤول',
      'admin.phone2': 'الهاتف',
      'admin.fiscalCode': 'الرمز الضريبي',
      'admin.representative': 'الممثل',
      'admin.observation': 'ملاحظة',
      'admin.quickAdd': 'إضافة سريعة',
      'admin.packageCount': 'عدد العلب',
      'admin.unitPrice': 'سعر الوحدة',
      'admin.discount': 'الخصم',
      'admin.fodec': 'فوديك',
      'admin.consumptionTax': 'ضريبة الاستهلاك',
      'admin.vat': 'ضريبة القيمة المضافة',
      'admin.totalHt': 'المجموع بدون ضريبة',
      'admin.totalTtc': 'المجموع شامل الضريبة',
      'admin.paidAmount': 'المبلغ المدفوع',
      'admin.paymentMode': 'طريقة الدفع',
      'admin.pieceRef': 'مرجع القطعة',
      'admin.bank': 'البنك',
      'admin.dueDate': 'تاريخ الاستحقاق',
      'admin.remainingBalance': 'الرصيد المتبقي',
      'admin.commitment': 'الالتزام',
      'admin.withholdingBase': 'أساس الاقتطاع',
      'admin.withholdingAmount': 'مبلغ الاقتطاع',
      'admin.newOrderTitle': 'طلب جديد',
      'admin.editOrderTitle': 'تعديل الطلب',
      'admin.orderCreated': 'تم إنشاء الطلب',
      'admin.orderUpdated': 'تم تحديث الطلب',
      'admin.faceRegistration': 'تسجيل الوجه',
      'admin.openCamera': 'فتح الكاميرا',
      'admin.captureFace': 'التقاط الوجه',
      'admin.faceRegistered': 'تم تسجيل الوجه',
      'admin.faceDeleted': 'تم حذف الوجه',
      'admin.cameraError': 'خطأ في الكاميرا',
      'admin.userUpdated': 'تم تحديث المستخدم',
      'admin.expenseAdded': 'تمت إضافة المصروف',
      'admin.expenseUpdated': 'تم تحديث المصروف',
      'admin.materialAdded': 'تمت إضافة المادة',
      'admin.materialUpdated': 'تم تحديث المادة',
      'admin.materialDeleted': 'تم حذف المادة',
      'admin.productDeleted': 'تم حذف المنتج',
      'admin.paymentValidated': 'تم تأكيد الدفع',
      'admin.validationError': 'خطأ في التحقق',
      'admin.loading': 'جاري التحميل...',
      'admin.noData': 'لا توجد بيانات',
      'admin.noProductsFound': 'لم يتم العثور على منتجات',
      'admin.noOrdersFound': 'لم يتم العثور على طلبات',
      'admin.noUsersFound': 'لم يتم العثور على مستخدمين',
      'admin.noEmployeesFound': 'لم يتم العثور على موظفين',
      'admin.noExpensesFound': 'لم يتم العثور على مصاريف',
      'admin.noMaterialsFound': 'لم يتم العثور على مواد',
      'admin.noDocumentsFound': 'لم يتم العثور على وثائق',
      'admin.previous': 'السابق',
      'admin.next': 'التالي',
      'admin.page': 'الصفحة',
      'admin.of': 'من',
      'admin.resetFilters': 'إعادة تعيين الفلاتر',
      'admin.showForm': 'إظهار النموذج',
      'admin.hideForm': 'إخفاء النموذج',
      'admin.edit': 'تعديل',
      'admin.delete': 'حذف',
      'admin.view': 'عرض',
      'admin.details': 'التفاصيل',
      'admin.status': 'الحالة',
      'admin.date': 'التاريخ',
      'admin.amount': 'المبلغ',
      'admin.client': 'العميل',
      'admin.shop_name': 'اسم المتجر',
      'admin.total': 'المجموع',
      'admin.subtotal': 'المجموع الفرعي',
      'admin.tax': 'الضريبة',
      'admin.grandTotal': 'المجموع الكلي',
      'admin.activeProducts': 'المنتجات النشطة',
      'admin.bottleStockValue': 'قيمة مخزون العلب',
      'admin.basesToWatch': 'القواعد للمراقبة',
      'admin.visiblePerfumes': 'العطور المرئية',
      'admin.visibleBaseStock': 'المخزون الأساسي المرئي',
      'admin.directorEntry': 'إدخال المدير',
      'admin.perBottle': 'لكل علبة',
      'admin.stockLines': 'خطوط المخزون',
      'admin.monthPurchases': 'مشتريات الشهر',
      'admin.materialStockValue': 'قيمة مخزون المواد',
      'admin.stockAlerts': 'تنبيهات المخزون',
      'admin.filteredDocs': 'الوثائق المفلترة',
      'admin.stockInvoices': 'فواتير المخزون',
      'admin.purchaseOrders': 'أوامر الشراء',
      'admin.visiblePerfumery': 'العطور المرئية',
      'admin.totalPaidOrders': 'إجمالي الطلبات المدفوعة',
      'admin.filteredResults': 'النتائج المفلترة',
      'admin.partialOrders': 'الطلبات الجزئية',
      'admin.activeEmployees': 'الموظفين النشطين',
      'admin.payroll': 'الرواتب',
      'admin.results': 'النتائج',
      'admin.activeUsers': 'المستخدمين النشطين',
      'admin.clients': 'العملاء',
      'admin.account': 'الحساب',
      'admin.noSharedAccess': 'لا يوجد وصول مشترك',
      'admin.noFaceRegistered': 'لم يتم تسجيل وجه',
      'admin.addFirstFace': 'أضف وجهاً أولاً للسماح لمتعاون واحد أو أكثر بتسجيل الدخول إلى حساب المدير',
      'admin.faceAuthorized': 'وجه مصرح به',
      'admin.paidRemaining': 'مدفوع / متبقي',
      'admin.bottle': 'علبة',
      'admin.create': 'إنشاء',
      'admin.optionalChoice': 'اختيار اختياري',
      'admin.invoiceGeneratedFrom': 'فاتورة مولدة من',
      'admin.orderGeneratedFrom': 'أمر شراء مولد من',
      'admin.purchaseOrder': 'أمر شراء',
      'admin.invoice': 'فاتورة',
      'admin.tariffApplied': 'تم تطبيق التعريفة',
      'admin.profile': 'الملف الشخصي',
      'admin.totalRemaining': 'إجمالي المتبقي',
      'admin.companyPerfumery': 'الشركة / العطور',
      'admin.statuses': 'الحالات',
      'admin.noNameAccess': 'وصول بدون اسم',
      'admin.addedOn': 'أضيف في',
      'admin.idLabel': 'المعرف',
      'admin.activeView': 'العرض النشط',
      'admin.location': 'الموقع',
      'admin.authorizedFaces': 'الوجوه المصرح بها',
      'admin.monthlySalary': 'الراتب الشهري',
      'admin.partialPayments': 'المدفوعات الجزئية',
      'admin.allOrders': 'جميع الطلبات',
      'admin.centralCatalog': 'الكتالوج المركزي',
      'admin.productsStock': 'المنتجات والمخزون',
      'admin.productsStockDesc': 'استعرض الكتالوج، راقب المخزون بالعلب والمواد الأولية، ثم افتح النموذج لإضافة أو تعديل منتج.',
      'admin.searchProduct': 'بحث عن منتج...',
      'admin.product': 'المنتج',
      'admin.family': 'الفئة',
      'admin.price': 'السعر',
      'admin.bottleStock': 'مخزون العلب',
      'admin.baseStock': 'المخزون الأساسي',
      'admin.productForm': 'نموذج المنتج',
      'admin.addOrEditProduct': 'إضافة أو تعديل منتج',
      'admin.backToList': 'العودة للقائمة',
      'admin.category': 'الفئة',
      'admin.code': 'الرمز',
      'admin.priceDT': 'السعر DT',
      'admin.bottleAlertThreshold': 'حد تنبيه العلب',
      'admin.rawMaterialStockMl': 'مخزون المواد الأولية (مل)',
      'admin.rawMaterialAlertMl': 'حد تنبيه المواد الأولية (مل)',
      'admin.sku': 'رمز SKU',
      'admin.barcode': 'الرمز الشريطي',
      'admin.active': 'نشط',
      'admin.saveProduct': 'حفظ المنتج',
      'admin.new': 'جديد',
      'admin.biometricAccess': 'الوصول البيومتري للمدير',
      'admin.controlAuthorizedFaces': 'تحكم في الوجوه المصرح بها بعرض أكثر احترافية',
      'admin.biometricDesc': 'سجل كل متعاون مصرح به على حساب المدير، واحتفظ برؤية واضحة للوصول النشط وافتح الكاميرا في نافذة أكثر أناقة.',
      'admin.sharedSecurity': 'أمان مشترك',
      'admin.adminTeam': 'فريق الإدارة',
      'admin.sharedAccountDesc': 'يمكن استخدام نفس الحساب من قبل عدة أشخاص مصرح بهم، كل منهم بوجهه المسجل.',
      'admin.adminAccount': 'حساب المدير',
      'admin.accountInfo': 'معلومات الحساب',
      'admin.accountInfoDesc': 'اعثر على معلومات حساب المدير المتصل حالياً.',
      'admin.sharedAccess': 'الوصول المشترك',
      'admin.addMultipleFaces': 'أضف عدة وجوه لتمكين عدة أشخاص من الشركة من الوصول إلى نفس حساب المدير.',
      'admin.personOrJobName': 'اسم الشخص أو المنصب',
      'admin.faceLabelPlaceholder': 'مثال: المدير، مسؤول المتجر، المحاسب',
      'admin.addFace': 'إضافة وجه',
      'admin.integratedCamera': 'الكاميرا المدمجة',
      'admin.frameAndCapture': 'ضع الوجه في الإطار ثم التقط',
      'admin.addingInProgress': 'إضافة قيد التنفيذ',
      'admin.captureThisFace': 'التقاط هذا الوجه',
      'admin.perfumeStock': 'مخزون العطور',
      'admin.allPerfumesTable': 'جدول جميع العطور',
      'admin.allPerfumesDesc': 'استعرض جميع العطور واترك المدير يدخل المخزون الأساسي بعدد العلب فقط.',
      'admin.searchPerfume': 'بحث عن عطر...',
      'admin.allCategories': 'جميع الفئات',
      'admin.sortNameAZ': 'ترتيب: الاسم أ-ي',
      'admin.sortNameZA': 'ترتيب: الاسم ي-أ',
      'admin.sortCategory': 'ترتيب: الفئة',
      'admin.sortBaseDesc': 'ترتيب: المخزون الأساسي تنازلي',
      'admin.sortBaseAsc': 'ترتيب: المخزون الأساسي تصاعدي',
      'admin.perfume': 'العطر',
      'admin.purchaseDirection': 'إدارة المشتريات والمخزون',
      'admin.materialStockTitle': 'المخزون والمواد الأولية',
      'admin.materialStockDesc2': 'أضف قواعد العطور، الكحول، الأصباغ، العلب الهشة، التذاكر والسدادات في جدول قابل للتعديل مع حساب تلقائي للمجموع بالدينار.',
      'admin.searchMaterial': 'بحث عن قاعدة، كحول، علبة...',
      'admin.addLine': 'إضافة سطر',
      'admin.article': 'المقال',
      'admin.unit': 'الوحدة',
      'admin.alert': 'تنبيه',
      'admin.note': 'ملاحظة',
      'admin.accountMgmt': 'إدارة الحسابات',
      'admin.usersProfiles': 'المستخدمين والملفات',
      'admin.usersProfilesDesc': 'استعرض ملفات المستخدمين، عدل معلوماتهم وعطل الحسابات إذا لزم الأمر.',
      'admin.searchUser': 'بحث عن مستخدم...',
      'admin.fullName': 'الاسم الكامل',
      'admin.perfumery': 'العطور',
      'admin.contact': 'الاتصال',
      'admin.userConsultation': 'استشارة المستخدم',
      'admin.userProfile': 'ملف المستخدم',
      'admin.firstName': 'الاسم الأول',
      'admin.lastName': 'الاسم الأخير',
      'admin.saveUser': 'حفظ المستخدم',
      'admin.orderPilot': 'إدارة الطلبات',
      'admin.ordersPayments': 'الطلبات والمدفوعات',
      'admin.ordersPaymentsDesc': 'استعرض الطلبات، اطلع على المجموع المدفوع، إيرادات اليوم وقم بإجراءات المتابعة على كل ملف.',
      'admin.allTypes': 'جميع الأنواع',
      'admin.allPerfumery': 'جميع العطور',
      'admin.allDeliveryStatus': 'جميع حالات التوصيل',
      'admin.allPaymentStatus': 'جميع حالات الدفع',
      'admin.from': 'من',
      'admin.to': 'إلى',
      'admin.createOrder': 'إنشاء طلب',
      'admin.showAll': 'عرض الكل',
      'admin.order': 'الطلب',
      'admin.delivery': 'التوصيل',
      'admin.orderCreation': 'إنشاء الطلب',
      'admin.createOrderForPerfumery': 'إنشاء طلب لمتجر عطور',
      'admin.createOrderDesc': 'اختر متجراً موجوداً، أضف العطور المطلوبة ثم سجل الطلب تحت حسابه.',
      'admin.validateDocument': 'تأكيد الوثيقة',
      'admin.nature': 'الطبيعة',
      'admin.depot': 'المستودع',
      'admin.orderNum': 'رقم الطلب',
      'admin.discountRate': 'نسبة الخصم',
      'admin.exceptionalRate': 'النسبة الاستثنائية',
      'admin.siteOrder': 'أمر شراء الموقع',
      'admin.stockInvoice': 'فاتورة مخزون العطور',
      'admin.searchPerfumery': 'بحث عن متجر عطور...',
      'admin.clientCode': 'رمز العميل',
      'admin.contactName': 'اسم الاتصال',
      'admin.address': 'العنوان',
      'admin.city': 'المدينة',
      'admin.postalCode': 'الرمز البريدي',
      'admin.balance': 'الرصيد',
      'admin.dueDate': 'تاريخ الاستحقاق',
      'admin.commitment': 'الالتزام',
      'admin.observations': 'الملاحظات',
      'admin.quickType': 'النوع السريع',
      'admin.profileLabel': 'الملف',
      'admin.qty': 'الكمية',
      'admin.noProductAdded': 'لم يتم إضافة أي منتج بعد.',
      'admin.designation': 'التسمية',
      'admin.action': 'الإجراء',
      'admin.transport': 'النقل',
      'admin.weightPackages': 'الوزن/العلب',
      'admin.cash': 'نقداً',
      'admin.check': 'شيك',
      'admin.transfer': 'تحويل',
      'admin.draft': 'سند',
      'admin.newBalance': 'الرصيد الجديد',
      'admin.fodecCict': 'فوديك/سيكت',
      'admin.totalVat': 'إجمالي TVA',
      'admin.withholdingTax': 'الاقتطاع من المصدر',
      'admin.totalPayable': 'إجمالي المستحق',
      'admin.saveOrder': 'حفظ الطلب',
      'admin.orderConsultation': 'استشارة الطلب',
      'admin.orderDetail': 'تفاصيل الطلب',
      'admin.clientLastName': 'اسم العميل',
      'admin.clientFirstName': 'اسم العميل الأول',
      'admin.docMgmt': 'إدارة الوثائق',
      'admin.invoicesOrders': 'الفواتير وأوامر الشراء',
      'admin.docMgmtDesc': 'اعثر على جميع وثائق الإدارة مع الترقيم والبحث السريع والإجراءات المباشرة حسب العطر، الهاتف، الاسم واللقب والتاريخ.',
      'admin.searchDoc': 'بحث شامل وثيقة، فاتورة، عطر...',
      'admin.hrMgmt': 'إدارة الموارد البشرية',
      'admin.employeesSalaries': 'الموظفين والرواتب',
      'admin.employeesSalariesDesc': 'استعرض كل موظف بسرعة، حالته وراتبه. أضف ملفاً جديداً عبر النموذج المخصص.',
      'admin.searchEmployee': 'بحث عن موظف...',
      'admin.employeeForm': 'نموذج الموظف',
      'admin.employeeCode': 'رمز الموظف',
      'admin.hireDate': 'تاريخ التوظيف',
      'admin.password': 'كلمة المرور',
      'admin.expenseTracking': 'متابعة المصاريف',
      'admin.expenseHistory': 'سجل المصاريف',
      'admin.expenseHistoryDesc': 'اعرض المصاريف الموجودة، ابحث عنها وافتح النموذج فقط عندما تريد إضافة أو تعديل.',
      'admin.searchExpense': 'بحث عن مصروف...',
      'admin.expenseForm': 'نموذج المصروف',
      'admin.addOrEditExpense': 'إضافة أو تعديل مصروف',
      'admin.rawMaterial': 'المواد الأولية',
      'admin.salary': 'الراتب',
      'admin.rent': 'الإيجار',
      'admin.other': 'أخرى',
      'admin.amountDT': 'المبلغ DT',
      'admin.saveExpense': 'حفظ المصروف',
      'admin.partialPayment': 'الدفع الجزئي',
      'admin.recordPartialPayment': 'تسجيل دفعة جزئية',
      'admin.alreadyPaid': 'مدفوع بالفعل',
      'admin.remainingToPay': 'المتبقي للدفع',
      'admin.amountPaidNow': 'المبلغ المدفوع الآن',
      'admin.secureAccess': 'الوصول الآمن',
      'admin.openDashboard': 'فتح لوحة التحكم',
      'admin.enterAdminCode': 'أدخل رمز المدير لعرض النظرة العامة.',
      'admin.accessCode': 'رمز الوصول',
      'admin.open': 'فتح',

      // ── Language button ──
      'lang.switch': 'العربية',
      'lang.switchBack': 'Français',
    }
  };

  // French is the default — stored as source text, no need for a dict.
  const STORAGE_KEY = 'idene_lang';
  let currentLang = localStorage.getItem(STORAGE_KEY) || 'fr';

  // ── Core translation function ─────────────────────────────
  function t(key) {
    if (currentLang === 'fr') return null; // Use original text
    return translations[currentLang]?.[key] || null;
  }

  // ── Apply translations to all [data-i18n] elements ────────
  function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      const translated = t(key);
      if (translated !== null) {
        // Store original text for reverting
        if (!el.hasAttribute('data-i18n-original')) {
          el.setAttribute('data-i18n-original', el.innerHTML);
        }
        if (el.innerHTML !== translated) {
          el.innerHTML = translated;
        }
      } else {
        // Revert to original
        const original = el.getAttribute('data-i18n-original');
        if (original !== null && el.innerHTML !== original) {
          el.innerHTML = original;
        }
      }
    });

    // Translate placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
      const key = el.getAttribute('data-i18n-placeholder');
      const translated = t(key);
      if (translated !== null) {
        if (!el.hasAttribute('data-i18n-placeholder-original')) {
          el.setAttribute('data-i18n-placeholder-original', el.placeholder);
        }
        if (el.placeholder !== translated) {
          el.placeholder = translated;
        }
      } else {
        const original = el.getAttribute('data-i18n-placeholder-original');
        if (original !== null && el.placeholder !== original) {
          el.placeholder = original;
        }
      }
    });
  }

  // ── Toggle RTL/LTR ────────────────────────────────────────
  function applyDirection() {
    const html = document.documentElement;
    if (currentLang === 'ar') {
      html.setAttribute('lang', 'ar');
      html.setAttribute('dir', 'rtl');
      document.body.classList.add('rtl-mode');
    } else {
      html.setAttribute('lang', 'fr');
      html.removeAttribute('dir');
      document.body.classList.remove('rtl-mode');
    }
  }

  // ── Update toggle button text ─────────────────────────────
  function updateToggleBtn() {
    const btns = document.querySelectorAll('.lang-toggle-btn');
    btns.forEach((btn) => {
      const label = btn.querySelector('.lang-toggle-label');
      const flag = btn.querySelector('.lang-toggle-flag');
      if (currentLang === 'ar') {
        if (label && label.textContent !== 'Français') label.textContent = 'Français';
        if (flag && flag.textContent !== '🇫🇷') flag.textContent = '🇫🇷';
      } else {
        if (label && label.textContent !== 'العربية') label.textContent = 'العربية';
        if (flag && flag.textContent !== '🇸🇦') flag.textContent = '🇸🇦';
      }
    });
  }

  // ── Toggle language ───────────────────────────────────────
  function toggleLanguage() {
    currentLang = currentLang === 'fr' ? 'ar' : 'fr';
    localStorage.setItem(STORAGE_KEY, currentLang);
    applyDirection();
    applyTranslations();
    updateToggleBtn();
  }

  // ── Initialize ────────────────────────────────────────────
  function init() {
    applyDirection();
    applyTranslations();

    // Bind click handlers to all toggle buttons
    document.querySelectorAll('.lang-toggle-btn').forEach((btn) => {
      btn.addEventListener('click', toggleLanguage);
    });

    updateToggleBtn();

    // Auto-translate dynamic content with a debounce to prevent page freezing
    let translationTimeout;
    const scheduleTranslate = () => {
        clearTimeout(translationTimeout);
        translationTimeout = setTimeout(() => {
            observer.disconnect();
            applyTranslations();
            updateToggleBtn();
            observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['placeholder'] });
        }, 120);
    };
    const observer = new MutationObserver((mutations) => {
        // Skip mutations caused by translation attributes changing
        const isOnlyAttributeChanges = mutations.every(m => m.attributeName && m.attributeName.startsWith('data-i18n-'));
        if (isOnlyAttributeChanges) return;

        scheduleTranslate();
    });
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['placeholder'] });
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for external use
  window.ideneI18n = {
    toggle: toggleLanguage,
    setLang(lang) {
      currentLang = lang;
      localStorage.setItem(STORAGE_KEY, currentLang);
      applyDirection();
      applyTranslations();
      updateToggleBtn();
    },
    getLang() {
      return currentLang;
    },
    t,
    apply: applyTranslations
  };
})();
