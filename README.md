The React-Luma Module is a Faster and Free Open-Source Magento 2 Luma or another Theme Optimiser and Hyva Theme Alternative. Actually, you don’t even need to change the Theme. It works as a composer module to improve your existing theme without re-platforming to Hyva, or it can be used to improve Hyva.

React-Luma is 20% faster than any M2 front-end, including Hyva, today!

100% Vanilla.JS(no framework was used to improve performance) and default magento CSS without LESS compilation. However, it can be easily extended by any JS (ReactJS, VueJS) or CSS(Tilewind) library of your choice.

<img width="781" alt="image" src="https://github.com/user-attachments/assets/adad25f3-d394-4b00-8661-52e3165f6af7" />

# Have you questions about integrating ReactJS or VueJS with Magento 2 frontend? This Magento module will help you
React Magento 2 implementation. This module explains how to add and use ReactJS or any other framework micro-frontend UI Components with Magento 2 and forget about Knockout/JQuery Magento 2 UI without migration to a new theme(Works with existing theme and designs). Checkout, admin, customer account, and any other part of your store can work using legacy Magento 2 JS implementation

# No Magento CSS and Magento JS
Enable only CSS Junk settings and 
```
php bin/magento config:set dev/js/move_script_to_bottom 1
```

![React + Magento 2](https://github.com/Genaker/reactmagento2/blob/master/KnockoutMagento2React.png)

# Updates:
- VueJS implementation 
- Magento Configuration enable React, VueJS
- Remove Magento's default JS Junk (Require, Knockout, jQuery) configuration. You will need to implement the required functionality or use Magento Open Source ReactJS Luma Theme
- CSS optimizer feature added. Add optimized CSS files to pub/static/styles-(l|m).css and replace the default Magento one 
- Magento ReactJS Luma theme released
- React-Luma module. Just install the module and optimize the theme

# Magento Blazing-Fast React Luma Theme Implementation:

<img src="https://raw.githubusercontent.com/Genaker/Luma-React-PWA-Magento-Theme/master/web/images/logo.jpeg" alt="magento react theme"/>
<br>
Repo is here: https://github.com/Genaker/Luma-React-PWA-Magento-Theme

# About the Magento 2 React implementation module 
You can add any modern library to extend the magento feature. React-Luma has basic magento features implemented using native JS without dependencie.s 
It is not a PWA or headless implementation, which is impossible to use with an existing website. Also, Single Page Application (SPA) PWA Magento 2 implementations have issues with Magento 2 API performance - too slow. This implementation is High-Performance integration with magento2 (with Magento 1 also easy to use) it uses inline JSON directly from the page. The same approach is used in Magento 2 backend and frontend checkout, color swatches by default. Also can use Ajax HTTP call to fetch data (not the best solution Magento API is slow and will increase the load on your backend server). You can also use my future project, "Microservices Magento," to fetch data.


# CSS improvements

You can also replace Magento CSS with your custom CSS files if you put them compiled into pub/static
```
$optimisedCSSFilePath = BP . '/pub/static/styles-m.css';
```


# VueJS support 

![Logo-Vuejs](https://user-images.githubusercontent.com/9213670/150036919-3486e016-3d37-4ffd-b4ee-a3a3bbc961e9.png)

VueJS is a progressive framework for building user interfaces. Unlike Magento 2 UI monolithic component, Vue is designed from the ground up to be incrementally adoptable. The core library is focused on the view layer only, and is easy to pick up and integrate with other libraries or existing projects. 

## JS libraries footprint: 
* ReactJS 17 - 11KB
* ReactDOM - 120KB
* Preact - 12KB
* HTML for Preact - 1KB
* RequireJS - 17KB
* VueJS - 94KB
* KnockoutJS - 67KB
* jQuery - 89Kb
* AlpineJS - 34KB

## My thougts About VUE.js PWA and Magento 2

Read this article: https://blog.vuestorefront.io/yehor-shytikov-pwa-is-a-real-revolution-not-only-in-ecommerce/

Magento 2  has a better framework optimization from a development perspective. It allows developers to use the dependency injection, plugin system (**which is considered harmful AOP software development principle** read: https://yegorshytikov.medium.com/magento-2-plug-ins-aod-architecture-are-harmful-dc23c4edb534), and XML notations for layout. I personally like the folder structure in which one directory is one module. Magento 1 was messy since one module contains several folders.

Magento 2 uses a modern Symphony approach but it still has a lot of legacy code. Even though it introduces a new way of embracing front-end development, however from a nowadays perspective it is not enough(legacy). No wonder as Magento 2 was released before Vue.js and React took the world popularity. These JS frameworks add more features for developers and - in general - provide more possibilities. 

# Install Magento ReactJS/VueJS extension via composer:
```
composer require genaker/react-luma
```

# Disable dafault Adobe's broken Magento 2 KnockoutJS UI Components from the frontend

![image](https://user-images.githubusercontent.com/9213670/154380709-8a0eaf05-266a-4aa6-9c1c-d79653dba38d.png)



# Magento 2 Admin module built with ReactJS instead of the legacy default JS:

(https://github.com/Genaker/Magento2OPcacheGUI)


# How to use WebPack with Magento 2

Install Node.JS (https://github.com/nodesource/distributions/blob/master/README.md) From the extension root (React/React) folder run:

```
npm install
npm start
```

Web Puck compiles everything automatically into React/React/view/base/web/js/index_bundle.js and deploys to pub/static without running static:deploy ssh command. LiveReload Plugin will reload Magento 2 pages automatically (not recommended solution by React Community. F5  more reliable solution). What you need just disable the Cache of your browser during development. Also, You can disable caching for single react bundle files via Nginx config.

# Easy deployment 

Usage of the web-pack sometimes is too difficult for Magento developers. I have created another way to use React with Magento without any compilation/complication.
Simply add these files to the Magento installation:
```
// React JS itself 
<script src='https://npmcdn.com/react-dom@15.3.0/dist/react-dom.min.js'></script>
// Babel to avoid compilation 
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

//Add to the page to render React component
<div id = "magentoReactApp"> </div>

//Write you scripts using babel 
<script type="text/babel">

var App = React.createClass({
	render: function() {
		return(
			<div className="App">
	             {/*Your APP code there */}
			</div>
		);
	}
});
ReactDOM.render(
	<App />,
	document.getElementById('magentoReactApp')
);
</script>
```

# Magento 2 live reload

This project aims to solve the case where you want assets served by your Magento app server, but still want reloads triggered from web packs build pipeline.

Add a script tag to your page pointed at the livereload server

```
<script src="http://localhost:35729/livereload.js"></script>
```
For development purposes, better disable browser caching (https://www.technipages.com/google-chrome-how-to-completely-disable-cache)

Disable react bundle caching for development purposes using Nginx (not tested yet):

```
location ~ index_bundle\.js {
    add_header Cache-Control no-cache;
    expires 0;
  }
```
