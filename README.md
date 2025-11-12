# **Aura — Garde-robe digitale**

> **Projet de fin d’étude CDA (Concepteur Développeur d’Applications)**  
> Développé par **Cansever ALKAN**

---

## **Concept**

**Aura** est une application web de **gestion de garde-robe** et de **création de tenues**.  
Elle permet à chaque utilisateur de **visualiser, organiser et valoriser ses vêtements** grâce à une interface claire, intuitive et élégante.

Pensée **mobile-first**, Aura combine **design, technologie et éthique**, en favorisant une approche responsable de la mode.

---

## **Objectif du projet**

Répondre à une problématique concrète :  
> *Comment aider les utilisateurs à mieux organiser et valoriser leur garde-robe, tout en favorisant une consommation vestimentaire plus responsable ?*

Beaucoup de personnes accumulent des vêtements sans vision d’ensemble :  
elles portent souvent les mêmes pièces, oublient certaines tenues, ou rachètent des articles similaires.  

**Aura** apporte une réponse simple et moderne :  
- une **vue globale** et claire de sa garde-robe,  
- un **outil visuel** pour composer des tenues,  
- un **espace personnel sécurisé**,  
- et une **approche intelligente** pour consommer mieux, sans renoncer au style.  

---

## **Fonctionnalités principales**

### **Espace utilisateur**
- Inscription / Connexion sécurisée (**Symfony Security**)  
- Gestion du profil et de la photo d’avatar  
- Modification du pseudonyme et du mot de passe  
- Suppression complète du compte (**conformité RGPD**)  

### **Dressing numérique**
- Ajout, affichage et suppression de vêtements avec image  
- Catégorisation par **type**, **couleur**, **saison**  
- Tri et filtrage personnalisés  
- Upload sécurisé (**jpg / jpeg / png / webp**)  

### **Création de tenues**
- Éditeur visuel basé sur **Fabric.js**  
- Déplacement, rotation et superposition libre des vêtements sur un canvas  
- Sauvegarde des tenues au format **JSON + snapshot PNG**  
- Module “**Inspire-moi**” *(préfiguration d’une IA locale)*  

### **Sécurité**
- Hashage des mots de passe avec **Argon2id** (recommandé par l’ANSSI)  
- Protection **CSRF** et validation des formulaires  
- Vérification du propriétaire via **Voter personnalisé**  
- Encodage automatique **Twig** (protection XSS)  
- Accès restreint selon les rôles : `ROLE_USER`, `ROLE_ADMIN`  

---

## **Stack technique**

| Côté | Technologie |
|------|--------------|
| **Backend** | Symfony 7.3 (PHP 8.2) |
| **Base de données** | MySQL 8.0 + Doctrine ORM |
| **Frontend** | Twig, HTML5, CSS3, JavaScript (Fabric.js) |
| **Sécurité** | Symfony Security, Argon2id |
| **Outils** | PhpStorm, Trello, Git/GitHub, Figma |
| **Méthodologie** | Agile Kanban (sprints + backlog Trello) |

---

## **Installation locale**

### **Prérequis**
- PHP ≥ 8.2  
- Composer  
- Symfony CLI  
- MySQL ou SQLite  
- PhpStorm (ou VS Code)

### **Installation**
```bash
git clone https://github.com/Neutredesign/Aura.git
cd Aura
composer install
```

---

## **Résultats & apprentissages**

Ce projet m’a permis de :
Concevoir un produit complet de la maquette au déploiement
Structurer une application multicouche MVC
Mettre en place une base de données fiable et sécurisée
Développer une application intuitive, performante et évolutive
Acquérir une réelle autonomie en développement web professionnel

---

## **Auteure**
Cansever ALKAN
Titre professionnel : Conceptrice Développeuse d’Applications (Niveau 6)
M2i Formation — Projet de fin d’études 2025
“Plus qu’un look, une aura.”
