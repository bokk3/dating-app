class DatingApp {
    constructor() {
        this.currentUser = null;
        this.currentScreen = 'loading';
        this.cards = [];
        this.matches = [];
        this.currentCardIndex = 0;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.checkAuthStatus();
    }

    setupEventListeners() {
        // Auth form listeners
        document.getElementById('loginForm').addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('registerForm').addEventListener('submit', (e) => this.handleRegister(e));
        document.getElementById('showRegister').addEventListener('click', () => this.showScreen('register'));
        document.getElementById('showLogin').addEventListener('click', () => this.showScreen('login'));
        document.getElementById('logoutBtn').addEventListener('click', () => this.handleLogout());

        // Navigation listeners
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Swipe action listeners
        document.getElementById('likeBtn').addEventListener('click', () => this.handleSwipe(true));
        document.getElementById('rejectBtn').addEventListener('click', () => this.handleSwipe(false));

        // Chat listeners
        document.getElementById('backToMatches').addEventListener('click', () => this.showMainScreen('matches'));
        document.getElementById('sendMessage').addEventListener('click', () => this.sendMessage());
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });

        // Touch/swipe listeners for cards
        this.setupCardSwipeListeners();
    }

    setupCardSwipeListeners() {
        let startX, startY, currentX, currentY;
        let isDragging = false;
        const cardStack = document.getElementById('cardStack');

        cardStack.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            isDragging = true;
        }, { passive: true });

        cardStack.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            const touch = e.touches[0];
            currentX = touch.clientX;
            currentY = touch.clientY;
            
            const deltaX = currentX - startX;
            const card = cardStack.querySelector('.profile-card:last-child');
            
            if (card) {
                const rotation = deltaX * 0.1;
                card.style.transform = `translateX(${deltaX}px) rotate(${rotation}deg)`;
                
                // Show like/dislike indicators
                if (deltaX > 50) {
                    card.classList.add('like-preview');
                    card.classList.remove('dislike-preview');
                } else if (deltaX < -50) {
                    card.classList.add('dislike-preview');
                    card.classList.remove('like-preview');
                } else {
                    card.classList.remove('like-preview', 'dislike-preview');
                }
            }
        }, { passive: true });

        cardStack.addEventListener('touchend', () => {
            if (!isDragging) return;
            isDragging = false;
            
            const deltaX = currentX - startX;
            const card = cardStack.querySelector('.profile-card:last-child');
            
            if (card) {
                if (Math.abs(deltaX) > 100) {
                    // Swipe threshold reached
                    this.animateCardExit(card, deltaX > 0);
                    this.handleSwipe(deltaX > 0);
                } else {
                    // Snap back
                    card.style.transform = '';
                    card.classList.remove('like-preview', 'dislike-preview');
                }
            }
        }, { passive: true });
    }

    animateCardExit(card, isLike) {
        const direction = isLike ? 1 : -1;
        card.style.transform = `translateX(${direction * window.innerWidth}px) rotate(${direction * 30}deg)`;
        card.style.opacity = '0';
        
        setTimeout(() => {
            if (card.parentNode) {
                card.remove();
            }
        }, 300);
    }

    async checkAuthStatus() {
        try {
            const response = await fetch('/api/auth/status');
            const data = await response.json();
            
            if (data.authenticated) {
                this.currentUser = data.user;
                this.showScreen('main');
                this.loadDiscoverCards();
            } else {
                this.showScreen('login');
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            this.showScreen('login');
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.currentUser = data.user;
                this.showScreen('main');
                this.loadDiscoverCards();
                this.showNotification('Welcome back!', 'success');
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            this.showNotification('Login failed. Please try again.', 'error');
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            this.showNotification('Passwords do not match', 'error');
            return;
        }
        
        try {
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showNotification('Registration successful! Please check your email to verify your account.', 'success');
                this.showScreen('login');
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            this.showNotification('Registration failed. Please try again.', 'error');
        }
    }

    async handleLogout() {
        try {
            await fetch('/api/auth/logout', { method: 'POST' });
            this.currentUser = null;
            this.showScreen('login');
            this.showNotification('Logged out successfully', 'success');
        } catch (error) {
            console.error('Logout failed:', error);
        }
    }

    handleNavigation(e) {
        const screen = e.currentTarget.dataset.screen;
        
        // Update active nav item
        document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
        e.currentTarget.classList.add('active');
        
        this.showMainScreen(screen);
        
        // Load data for the screen
        switch (screen) {
            case 'discover':
                this.loadDiscoverCards();
                break;
            case 'matches':
                this.loadMatches();
                break;
            case 'profile':
                this.loadProfile();
                break;
        }
    }

    async loadDiscoverCards() {
        try {
            const response = await fetch('/api/discover');
            const data = await response.json();
            
            this.cards = data.profiles || [];
            this.renderCards();
        } catch (error) {
            console.error('Failed to load cards:', error);
        }
    }

    renderCards() {
        const cardStack = document.getElementById('cardStack');
        cardStack.innerHTML = '';
        
        if (this.cards.length === 0) {
            cardStack.innerHTML = `
                <div class="no-cards">
                    <h3>No more profiles to show</h3>
                    <p>Check back later for new matches!</p>
                </div>
            `;
            return;
        }
        
        // Show up to 3 cards in stack
        const cardsToShow = this.cards.slice(0, 3);
        
        cardsToShow.forEach((profile, index) => {
            const card = this.createProfileCard(profile, index);
            cardStack.appendChild(card);
        });
    }

    createProfileCard(profile, stackIndex) {
        const card = document.createElement('div');
        card.className = 'profile-card';
        card.style.zIndex = 10 - stackIndex;
        card.style.transform = `scale(${1 - stackIndex * 0.02}) translateY(${stackIndex * 4}px)`;
        
        const age = this.calculateAge(profile.date_of_birth);
        
        card.innerHTML = `
            <img class="card-image" src="${profile.profile_picture || '/images/default-avatar.png'}" alt="${profile.first_name}">
            <div class="card-info">
                <div class="card-name">${profile.first_name}</div>
                <div class="card-age">${age} years old</div>
                <div class="card-bio">${profile.bio || 'No bio available'}</div>
            </div>
            <div class="like-indicator">LIKE</div>
            <div class="dislike-indicator">NOPE</div>
        `;
        
        return card;
    }

    calculateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age;
    }

    async handleSwipe(isLike) {
        if (this.cards.length === 0) return;
        
        const currentProfile = this.cards[0];
        
        try {
            const response = await fetch('/api/swipe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    swiped_id: currentProfile.user_id,
                    is_like: isLike
                })
            });
            
            const data = await response.json();
            
            if (data.match) {
                this.showMatchModal(currentProfile);
            }
            
            // Remove current card and show next
            this.cards.shift();
            this.renderCards();
            
            // Load more cards if running low
            if (this.cards.length < 5) {
                this.loadDiscoverCards();
            }
            
        } catch (error) {
            console.error('Swipe failed:', error);
        }
    }

    showMatchModal(profile) {
        // Create and show match modal
        const modal = document.createElement('div');
        modal.className = 'match-modal';
        modal.innerHTML = `
            <div class="match-content">
                <h2>It's a Match! 💕</h2>
                <div class="match-avatars">
                    <img src="${this.currentUser.profile_picture || '/images/default-avatar.png'}" alt="You">
                    <img src="${profile.profile_picture || '/images/default-avatar.png'}" alt="${profile.first_name}">
                </div>
                <p>You and ${profile.first_name} liked each other!</p>
                <div class="match-actions">
                    <button class="btn btn-secondary" onclick="this.closest('.match-modal').remove()">Keep Swiping</button>
                    <button class="btn btn-primary" onclick="app.openChat(${profile.user_id}); this.closest('.match-modal').remove();">Send Message</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (modal.parentNode) {
                modal.remove();
            }
        }, 5000);
    }

    async loadMatches() {
        try {
            const response = await fetch('/api/matches');
            const data = await response.json();
            
            this.matches = data.matches || [];
            this.renderMatches();
        } catch (error) {
            console.error('Failed to load matches:', error);
        }
    }

    renderMatches() {
        const matchesList = document.getElementById('matchesList');
        
        if (this.matches.length === 0) {
            matchesList.innerHTML = `
                <div class="no-matches">
                    <h3>No matches yet</h3>
                    <p>Start swiping to find your perfect match!</p>
                </div>
            `;
            return;
        }
        
        matchesList.innerHTML = this.matches.map(match => `
            <div class="match-item" onclick="app.openChat(${match.user_id})">
                <img class="match-avatar" src="${match.profile_picture || '/images/default-avatar.png'}" alt="${match.first_name}">
                <div class="match-info">
                    <div class="match-name">${match.first_name}</div>
                    <div class="match-last-message">${match.last_message || 'Say hello!'}</div>
                </div>
                <div class="match-time">${match.last_message_time || ''}</div>
            </div>
        `).join('');
    }

    openChat(userId) {
        this.currentChatUser = userId;
        this.showScreen('chat');
        this.loadChatMessages(userId);
    }

    async loadChatMessages(userId) {
        try {
            const response = await fetch(`/api/chat/${userId}`);
            const data = await response.json();
            
            this.renderChatMessages(data.messages || []);
            
            // Update chat header
            const match = this.matches.find(m => m.user_id === userId);
            if (match) {
                document.getElementById('chatAvatar').src = match.profile_picture || '/images/default-avatar.png';
                document.getElementById('chatName').textContent = match.first_name;
            }
        } catch (error) {
            console.error('Failed to load chat messages:', error);
        }
    }

    renderChatMessages(messages) {
        const chatMessages = document.getElementById('chatMessages');
        
        chatMessages.innerHTML = messages.map(message => `
            <div class="message ${message.sender_id === this.currentUser.id ? 'sent' : 'received'}">
                <div class="message-bubble">
                    ${message.message}
                </div>
            </div>
        `).join('');
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message) return;
        
        try {
            const response = await fetch('/api/chat/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    recipient_id: this.currentChatUser,
                    message: message
                })
            });
            
            if (response.ok) {
                messageInput.value = '';
                this.loadChatMessages(this.currentChatUser);
            }
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    }

    async loadProfile() {
        try {
            const response = await fetch('/api/profile');
            const data = await response.json();
            
            this.renderProfile(data.profile);
        } catch (error) {
            console.error('Failed to load profile:', error);
        }
    }

    renderProfile(profile) {
        const profileContent = document.getElementById('profileContent');
        
        profileContent.innerHTML = `
            <div class="profile-section">
                <div class="profile-photo">
                    <img src="${profile?.profile_picture || '/images/default-avatar.png'}" alt="Profile Picture">
                    <button class="edit-photo-btn">📷</button>
                </div>
                <div class="profile-info">
                    <h3>${profile?.first_name || 'Not set'} ${profile?.last_name || ''}</h3>
                    <p>${profile ? this.calculateAge(profile.date_of_birth) : 0} years old</p>
                </div>
            </div>
            
            <div class="profile-form">
                <div class="form-group">
                    <label>Bio</label>
                    <textarea placeholder="Tell us about yourself...">${profile?.bio || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label>Interested In</label>
                    <select>
                        <option value="male" ${profile?.interested_in === 'male' ? 'selected' : ''}>Men</option>
                        <option value="female" ${profile?.interested_in === 'female' ? 'selected' : ''}>Women</option>
                        <option value="both" ${profile?.interested_in === 'both' ? 'selected' : ''}>Both</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Maximum Distance (km)</label>
                    <input type="range" min="1" max="100" value="${profile?.max_distance || 50}">
                    <span class="range-value">${profile?.max_distance || 50} km</span>
                </div>
                
                <button class="btn btn-primary">Save Changes</button>
            </div>
        `;
    }

    showScreen(screenName) {
        document.querySelectorAll('.screen').forEach(screen => screen.classList.remove('active'));
        document.getElementById(screenName).classList.add('active');
        this.currentScreen = screenName;
    }

    showMainScreen(screenName) {
        document.querySelectorAll('.main-screen').forEach(screen => screen.classList.remove('active'));
        document.getElementById(screenName).classList.add('active');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new DatingApp();
});

// Service Worker registration for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(error => console.log('SW registration failed'));
    });
}