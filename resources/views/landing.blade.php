<!doctype html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Consorcios Villegas EIRL - Alimentos Balanceados para Ganado en Chiclayo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="author" content="Consorcios Villegas EIRL" />
    <meta name="description" content="Consorcios Villegas EIRL ofrece alimentos balanceados de alta calidad para aves, vacas, cerdos y caballos. Nutrición animal confiable en Chiclayo - Lambayeque." />
    <link rel="shortcut icon" href="{{asset('assets/favicon.ico')}}" type="image/x-icon">

    <link rel="canonical" href="https://www.consorciosvillegas.com/">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{asset('bootstrap-icons-1.13.1/bootstrap-icons.min.css')}}" />
    
    <!-- Landing Page Styles -->
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}" />
</head>
<body>
    <!-- Navigation -->
    <nav class="landing-nav" id="mainNav">
        <div class="nav-container">
            <a href="#inicio" class="nav-brand">
                <div class="nav-logo">
                    <img src="{{ asset('assets/favicon.ico') }}" alt="Consorcio Villegas Logo">
                </div>
                <div class="nav-brand-text">
                    <h1>CONSORCIOS VILLEGAS E.I.R.L.</h1>
                    <p>Alimentos Balanceados</p>
                </div>
            </a>
            <div class="nav-links">
                <a href="#nosotros" class="nav-link">Nosotros</a>
                <a href="#productos" class="nav-link">Productos</a>
                <a href="#calidad" class="nav-link">Calidad</a>
                <a href="#contacto" class="nav-link">Contacto</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <!-- Video Background -->
        <div class="hero-video-wrapper">
            <video class="hero-video" autoplay muted loop playsinline>
                <source src="{{ asset('assets/video.mp4') }}" type="video/mp4">
            </video>
            <div class="hero-video-overlay"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-badge animate-fade-in">
                <i class="bi bi-award-fill"></i> Calidad Garantizada Desde 2012
            </div>
            <h1 class="hero-title animate-fade-in">Nutrición de Excelencia para su Ganado</h1>
            <p class="hero-subtitle animate-fade-in">
                Somos líderes en la producción de alimentos balanceados de alta calidad para vacas, cerdos y caballos. 
                Nutrición completa que garantiza el mejor rendimiento y salud de sus animales.
            </p>
            <div class="hero-cta animate-fade-in">
                <a href="#productos" class="btn-primary">
                    <span>Ver Productos</span>
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </a>
                <a href="#contacto" class="btn-secondary">
                    <i class="bi bi-telephone-fill"></i>
                    <span>Contactar</span>
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <i class="bi bi-chevron-down"></i>
        </div>
    </section>

    <!-- About Us Section (Nosotros) -->
    <section class="about" id="nosotros">
        <div class="section-container">
            <div class="about-grid">
                <div class="about-content">
                    <span class="section-badge">Nuestra Historia</span>
                    <h2 class="section-title">Más de 13 Años de Experiencia</h2>
                    <p class="about-text">
                        Desde 2012, <strong>Consorcios Villegas E.I.R.L.</strong> se ha consolidado como líder en la industria 
                        de alimentos balanceados para animales en el norte del Perú. Nacimos con la visión de proporcionar nutrición de 
                        calidad superior que mejore la productividad y el bienestar del ganado.
                    </p>
                    <p class="about-text">
                        Nuestra misión es simple pero poderosa: <em>ofrecer productos nutricionales innovadores que 
                        maximicen el rendimiento de cada animal, respaldados por investigación científica y un 
                        compromiso inquebrantable con la excelencia</em>.
                    </p>
                    <div class="about-values">
                        <div class="value-item">
                            <i class="bi bi-heart-fill"></i>
                            <div>
                                <h4>Pasión</h4>
                                <p>Por el bienestar animal</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <i class="bi bi-lightbulb-fill"></i>
                            <div>
                                <h4>Innovación</h4>
                                <p>Fórmulas de vanguardia</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <i class="bi bi-shield-check"></i>
                            <div>
                                <h4>Confianza</h4>
                                <p>Resultados comprobados</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="stat-number" data-target="13">0</div>
                        <div class="stat-label">Años de Experiencia</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-number" data-target="5000">0</div>
                        <div class="stat-label">Clientes Satisfechos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-number" data-target="50">0</div>
                        <div class="stat-label">Toneladas Mensuales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-award"></i>
                        </div>
                        <div class="stat-number" data-target="100">0</div>
                        <div class="stat-label">% Satisfacción</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products" id="productos">
        <div class="section-container">
            <div class="section-header">
                <span class="section-badge">Nuestros Productos</span>
                <h2 class="section-title">Alimentos Balanceados Especializados</h2>
                <p class="section-subtitle">
                    Fórmulas nutricionales diseñadas específicamente para cada tipo de animal, 
                    garantizando un desarrollo óptimo y máximo rendimiento.
                </p>
            </div>
            
            <div class="products-grid">
                <!-- Vacas -->
                <div class="product-card">
                    <div class="product-icon">
                        <i class="bi bi-brightness-high-fill"></i>
                    </div>
                    <h3 class="product-name">Alimento para Vacas</h3>
                    <p class="product-description">
                        Fórmula especialmente diseñada para ganado bovino, optimizando la producción 
                        de leche y el desarrollo muscular.
                    </p>
                    <ul class="product-features">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Alto contenido proteico
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Vitaminas y minerales esenciales
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Mejor digestibilidad
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Incrementa producción de leche
                        </li>
                    </ul>
                    <a href="#contacto" class="product-btn">Solicitar Información</a>
                </div>

                <!-- Cerdos -->
                <div class="product-card">
                    <div class="product-icon">
                        <i class="bi bi-piggy-bank-fill"></i>
                    </div>
                    <h3 class="product-name">Alimento para Cerdos</h3>
                    <p class="product-description">
                        Nutrición balanceada para todas las etapas de crecimiento del cerdo, 
                        desde lechones hasta finalización.
                    </p>
                    <ul class="product-features">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Crecimiento acelerado
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Mejor conversión alimenticia
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Sistema inmune fortalecido
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Calidad de carne superior
                        </li>
                    </ul>
                    <a href="#contacto" class="product-btn">Solicitar Información</a>
                </div>

                <!-- Caballos -->
                <div class="product-card">
                    <div class="product-icon">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <h3 class="product-name">Alimento para Caballos</h3>
                    <p class="product-description">
                        Nutrición premium para caballos de trabajo, deporte y recreación, 
                        manteniendo energía y vitalidad óptimas.
                    </p>
                    <ul class="product-features">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Energía sostenida
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Pelaje brillante y saludable
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Fortalece huesos y articulaciones
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Mejora rendimiento deportivo
                        </li>
                    </ul>
                    <a href="#contacto" class="product-btn">Solicitar Información</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quality Section (Calidad) -->
    <section class="quality" id="calidad">
        <div class="section-container">
            <div class="section-header">
                <span class="section-badge">Nuestro Compromiso</span>
                <h2 class="section-title">Calidad Sin Compromisos</h2>
                <p class="section-subtitle">
                    Cada producto que sale de nuestras instalaciones cumple con los más rigurosos 
                    estándares de calidad internacional.
                </p>
            </div>

            <div class="quality-grid">
                <div class="quality-card">
                    <div class="quality-icon">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </div>
                    <h3 class="quality-title">Compromiso con la Calidad</h3>
                    <p class="quality-description">
                        Trabajamos siguiendo altos estándares y buenas prácticas inspiradas en metodologías reconocidas a nivel internacional, asegurando consistencia y calidad en cada lote producido.
                    </p>
                    <ul class="quality-badges">
                        <li><span class="badge">Estándares Internacionales</span></li>
                        <li><span class="badge">Buenas Prácticas</span></li>
                        <li><span class="badge">Metodologías Profesionales</span></li>
                    </ul>
                </div>

                <div class="quality-card">
                    <div class="quality-icon">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <h3 class="quality-title">Planta de Producción</h3>
                    <p class="quality-description">
                        Contamos con una planta de producción con equipos modernos donde analizamos 
                        cada materia prima y producto terminado.
                    </p>
                    <ul class="quality-list">
                        <li><i class="bi bi-check"></i> Análisis nutricional completo</li>
                        <li><i class="bi bi-check"></i> Control microbiológico</li>
                        <li><i class="bi bi-check"></i> Verificación de formulaciones</li>
                    </ul>
                </div>

                <div class="quality-card">
                    <div class="quality-icon">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <h3 class="quality-title">Tecnología de Punta</h3>
                    <p class="quality-description">
                        Invertimos constantemente en tecnología de procesamiento moderno para 
                        garantizar la mejor calidad nutricional.
                    </p>
                    <ul class="quality-list">
                        <li><i class="bi bi-check"></i> Peletizado de precisión</li>
                        <li><i class="bi bi-check"></i> Mezclado homogéneo</li>
                        <li><i class="bi bi-check"></i> Control automatizado</li>
                    </ul>
                </div>

                <div class="quality-card">
                    <div class="quality-icon">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <h3 class="quality-title">Equipo Experto</h3>
                    <p class="quality-description">
                        Profesionales especializados supervisan cada etapa 
                        del proceso productivo.
                    </p>
                    <ul class="quality-list">
                        <li><i class="bi bi-check"></i> Experiencia en el rubro</li>
                        <li><i class="bi bi-check"></i> Equipo proesional</li>
                        <li><i class="bi bi-check"></i> Asesoría personalizada</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section (Contacto) -->
    <section class="contact" id="contacto">
        <div class="section-container">
            <div class="contact-wrapper">
                <div class="contact-info">
                    <span class="section-badge">Contáctanos</span>
                    <h2 class="section-title">Estamos Aquí para Ayudarte</h2>
                    <p class="contact-text">
                        ¿Tienes preguntas sobre nuestros productos? ¿Necesitas asesoría personalizada? 
                        Nuestro equipo de expertos está listo para asistirte.
                    </p>

                    <div class="contact-methods">
                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div class="method-details">
                                <h4>Teléfono</h4>
                                <p>+51 978 431 737</p>
                                <span class="method-note">Lun - Sáb: 8:00 AM - 6:00 PM</span>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="method-details">
                                <h4>Email</h4>
                                <p>info@consorciosvillegas.com</p>
                                <span class="method-note">Respuesta en 24 horas</span>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="method-details">
                                <h4>Ubicación</h4>
                                <p>Carretera Pomalca CPM Villa El Progreso KM. 3 / Chiclayo - Lambayeque</p>
                                <span class="method-note">Visítanos de Lun - Sáb</span>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="method-icon">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div class="method-details">
                                <h4>WhatsApp</h4>
                                <p>+51 978 431 737</p>
                                <span class="method-note">Atención inmediata</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form-wrapper">
                    <form class="contact-form" id="contactForm">
                        <h3 class="form-title">Envíanos un Mensaje</h3>
                        
                        <div class="form-group">
                            <label for="name">Nombre Completo *</label>
                            <div class="input-wrapper">
                                <i class="bi bi-person-fill"></i>
                                <input type="text" id="name" name="name" required placeholder="Tu nombre">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Correo Electrónico *</label>
                            <div class="input-wrapper">
                                <i class="bi bi-envelope-fill"></i>
                                <input type="email" id="email" name="email" required placeholder="correo@ejemplo.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Teléfono *</label>
                            <div class="input-wrapper">
                                <i class="bi bi-telephone-fill"></i>
                                <input type="tel" id="phone" name="phone" required placeholder="+51 999999999">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject">Asunto *</label>
                            <div class="input-wrapper">
                                <i class="bi bi-chat-left-text-fill"></i>
                                <select id="subject" name="subject" required>
                                    <option value="">Selecciona un asunto</option>
                                    <option value="productos">Información de Productos</option>
                                    <option value="cotizacion">Solicitar Cotización</option>
                                    <option value="asesoria">Asesoría Técnica</option>
                                    <option value="distribuidor">Ser Distribuidor</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">Mensaje *</label>
                            <div class="input-wrapper">
                                <i class="bi bi-pencil-fill"></i>
                                <textarea id="message" name="message" rows="4" required placeholder="Cuéntanos en qué podemos ayudarte..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            <span>Enviar Mensaje</span>
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="{{ asset('assets/favicon.ico') }}" alt="Logo">
                </div>
                <h3>CONSORCIOS VILLEGAS E.I.R.L.</h3>
                <p>
                    Líderes en la producción de alimentos balanceados de alta calidad. 
                    Comprometidos con la nutrición y el bienestar animal desde 2012.
                </p>
                <div class="footer-social">
                    <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" title="Twitter"><i class="bi bi-twitter"></i></a>
                    <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Productos</h4>
                <ul>
                    <li><a href="#productos">Alimento para Vacas</a></li>
                    <li><a href="#productos">Alimento para Cerdos</a></li>
                    <li><a href="#productos">Alimento para Caballos</a></li>
                    <li><a href="#productos">Suplementos Nutricionales</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Empresa</h4>
                <ul>
                    <li><a href="#nosotros">Nosotros</a></li>
                    <li><a href="#calidad">Calidad</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                    <li><a href="{{ route('login') }}">Acceso Administrador</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Contacto</h4>
                <ul>
                    <li><a href="tel:+1234567890"><i class="bi bi-telephone"></i> +51 978 431 737</a></li>
                    <li><a href="mailto:info@consorciosvillegas.com"><i class="bi bi-envelope"></i> info@consorciosvillegas.com</a></li>
                    <li><i class="bi bi-geo-alt"></i> Carretera Pomalca CPM Villa El Progreso KM. 3 / Chiclayo - Lambayeque</li>
                    <li><i class="bi bi-clock"></i> Lun - Sáb: 8:00 AM - 6:00 PM</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} Consorcios Villegas. Todos los derechos reservados. | Desarrollado por <strong>IncanatoApps</strong></p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Hero video autoplay handler
        (function() {
            const heroVideo = document.querySelector('.hero-video');
            if (heroVideo) {
                heroVideo.muted = true;
                heroVideo.play().catch(function() {
                    document.body.addEventListener('click', function() {
                        heroVideo.play();
                    }, { once: true });
                });
            }
        })();

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navHeight = document.getElementById('mainNav').offsetHeight;
                    const targetPosition = target.offsetTop - navHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animated counters for stats
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const animateCounter = (element) => {
            const target = parseInt(element.getAttribute('data-target'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    element.textContent = Math.floor(current) + '+';
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target + '+';
                }
            };

            updateCounter();
        };

        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-number');
                    counters.forEach(counter => {
                        animateCounter(counter);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const aboutSection = document.querySelector('.about');
        if (aboutSection) {
            statsObserver.observe(aboutSection);
        }

        // Contact form handling
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Aquí puedes agregar tu lógica de envío de formulario
                alert('¡Gracias por contactarnos! Nos pondremos en contacto contigo pronto.');
                contactForm.reset();
            });
        }

        // Scroll indicator animation
        const scrollIndicator = document.querySelector('.scroll-indicator');
        if (scrollIndicator) {
            scrollIndicator.addEventListener('click', function() {
                document.querySelector('#nosotros').scrollIntoView({ behavior: 'smooth' });
            });
        }
    </script>
</body>
</html>
