# neos-blog
A Neos CMS package to integrate a node type based blog.

## Installation
Add the package in your site package composer.json

```
"require": {
  "obisconcept/neos-blog": "~1.0.0"
}
```

## Usage
Add the subroute to the `Routes.yaml` of the Flow application

```
-
  name: 'ObisConceptNeosBlog'
  uriPattern: '<ObisConceptNeosBlogSubroutes>'
  subRoutes:
    ObisConceptNeosBlogSubroutes:
      package: ObisConcept.NeosBlog
```