// CMS Content Loader for CY visa help
(function() {
    'use strict';

    // Load JSON file
    async function loadJSON(file) {
        try {
            const response = await fetch(file);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('Error loading ' + file + ':', error);
            return null;
        }
    }

    // Load Homepage Content
    async function loadHomepage() {
        const data = await loadJSON('/content/homepage.json');
        if (!data) return;

        // Update Carousel Slides
        const slides = document.querySelectorAll('.carousel-slide');
        
        if (slides[0]) {
            slides[0].querySelector('h1').textContent = data.slide1.heading;
            slides[0].querySelector('p').textContent = data.slide1.description;
            slides[0].querySelector('.cta-button').textContent = data.slide1.button;
            slides[0].querySelector('.cta-button').href = data.slide1.link;
        }
        
        if (slides[1]) {
            slides[1].querySelector('h1').textContent = data.slide2.heading;
            slides[1].querySelector('p').textContent = data.slide2.description;
            slides[1].querySelector('.cta-button').textContent = data.slide2.button;
            slides[1].querySelector('.cta-button').href = data.slide2.link;
        }
        
        if (slides[2]) {
            slides[2].querySelector('h1').textContent = data.slide3.heading;
            slides[2].querySelector('p').textContent = data.slide3.description;
            slides[2].querySelector('.cta-button').textContent = data.slide3.button;
            slides[2].querySelector('.cta-button').href = data.slide3.link;
        }

        // Update About Section
        const aboutContent = document.querySelector('.about-content');
        if (aboutContent && data.about) {
            const paragraphs = aboutContent.querySelectorAll('p');
            if (paragraphs[0]) paragraphs[0].textContent = data.about.para1;
            if (paragraphs[1]) paragraphs[1].textContent = data.about.para2;
        }

        // Update FAQs
        const faqItems = document.querySelectorAll('.faq-item');
        if (faqItems[0] && data.faq1) {
            faqItems[0].querySelector('.faq-question span').textContent = data.faq1.question;
            faqItems[0].querySelector('.faq-answer-content').textContent = data.faq1.answer;
        }
        if (faqItems[1] && data.faq2) {
            faqItems[1].querySelector('.faq-question span').textContent = data.faq2.question;
            faqItems[1].querySelector('.faq-answer-content').textContent = data.faq2.answer;
        }
        if (faqItems[2] && data.faq3) {
            faqItems[2].querySelector('.faq-question span').textContent = data.faq3.question;
            faqItems[2].querySelector('.faq-answer-content').textContent = data.faq3.answer;
        }
    }

    // Load Testimonials
    async function loadTestimonials() {
        const data = await loadJSON('/content/testimonials.json');
        if (!data || !data.items) return;

        const testimonialSlides = document.querySelectorAll('.testimonial-slide');
        
        data.items.forEach((testimonial, index) => {
            if (testimonialSlides[index] && testimonial.featured) {
                const slide = testimonialSlides[index];
                slide.querySelector('.testimonial-text').textContent = `"${testimonial.quote}"`;
                slide.querySelector('.testimonial-author').textContent = `- ${testimonial.author}`;
            }
        });
    }

    // Load Services
    async function loadServices() {
        const data = await loadJSON('/content/services.json');
        if (!data || !data.items) return;

        const servicesGrid = document.querySelector('.services-grid');
        if (!servicesGrid) return;

        // Clear existing services
        servicesGrid.innerHTML = '';

        // Add services from JSON
        data.items.forEach(service => {
            const serviceHTML = `
                <a href="${service.link}" class="service-card-link">
                    <div class="service-card">
                        <i class="${service.icon}"></i>
                        <h3>${service.title}</h3>
                        <p>${service.description}</p>
                    </div>
                </a>
            `;
            servicesGrid.innerHTML += serviceHTML;
        });
    }

    // Load Contact Info
    async function loadContact() {
        const data = await loadJSON('/content/contact.json');
        if (!data) return;

        // Update footer contact
        const footerSections = document.querySelectorAll('.footer-section');
        if (footerSections[0]) {
            const contactSection = footerSections[0];
            const paragraphs = contactSection.querySelectorAll('p');
            
            if (paragraphs[0]) {
                paragraphs[0].innerHTML = `<i class="fas fa-phone"></i> <a href="tel:${data.phone.replace(/\s/g, '')}" style="color: rgba(255,255,255,0.8); text-decoration: none;">${data.phone}</a>`;
            }
            if (paragraphs[1]) {
                paragraphs[1].innerHTML = `<i class="fas fa-envelope"></i> <a href="mailto:${data.email}" style="color: rgba(255,255,255,0.8); text-decoration: none;">${data.email}</a>`;
            }
            if (paragraphs[2]) {
                paragraphs[2].innerHTML = `<i class="fas fa-map-marker-alt"></i> <a href="${data.maps_link}" target="_blank" style="color: rgba(255,255,255,0.8); text-decoration: none;">${data.address1}<br>${data.address2}</a>`;
            }
        }

        // Update office hours
        if (footerSections[1] && data.hours) {
            const hoursSection = footerSections[1];
            const paragraphs = hoursSection.querySelectorAll('p');
            if (paragraphs[0]) paragraphs[0].textContent = data.hours.weekdays;
            if (paragraphs[1]) paragraphs[1].textContent = data.hours.saturday;
            if (paragraphs[2]) paragraphs[2].textContent = data.hours.sunday;
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Only load on homepage (index.html)
        if (window.location.pathname === '/' || window.location.pathname.includes('index.html')) {
            loadHomepage();
            loadTestimonials();
            loadServices();
        }
        
        // Load contact info on all pages (for footer)
        loadContact();
    });
})();
