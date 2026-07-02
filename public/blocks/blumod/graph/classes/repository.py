from app.graphdb import GraphDB


class Repository:

    
    @staticmethod
    def resources_without_learning_units(course_uri):

     sparql = f"""
PREFIX ade: <http://forjagraph.eus/onto#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT DISTINCT ?itemType ?label ?item
WHERE {{

    BIND(<{course_uri}> AS ?course)

    ?course ade:containsLearningUnit ?lu .
    ?course ade:hasAssessmentItem ?item .

    OPTIONAL {{
        ?item rdfs:label ?label .
    }}

    OPTIONAL {{
        ?item ade:resourceType ?itemTypeValue .
    }}

    BIND(COALESCE(?itemTypeValue, "resource") AS ?itemType)

    MINUS {{
        ?item ade:relatedToLearningUnit ?anyLu .
        ?course ade:containsLearningUnit ?anyLu .
    }}

}}
ORDER BY ?itemType ?label ?item
"""

     return GraphDB.query(sparql)   
  
    @staticmethod
    def resource_learningunit_relations(course_uri):

     sparql = f"""
    PREFIX ade: <http://forjagraph.eus/onto#>
    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

    SELECT ?source ?target ?sourceLabel ?targetLabel ?type ?sourceType ?targetType
    WHERE {{

        BIND(<{course_uri}> AS ?course)

        ?course ade:containsLearningUnit ?target .
        ?source ade:relatedToLearningUnit ?target .

        OPTIONAL {{ ?source rdfs:label ?l1 }}
        OPTIONAL {{ ?source ade:name ?l2 }}
        BIND(COALESCE(?l1, ?l2) AS ?sourceLabel)

        OPTIONAL {{ ?source ade:resourceType ?resourceTypeValue }}
        BIND(COALESCE(?resourceTypeValue, "resource") AS ?sourceType)

        OPTIONAL {{ ?target rdfs:label ?l3 }}
        OPTIONAL {{ ?target ade:name ?l4 }}
        BIND(COALESCE(?l3, ?l4) AS ?targetLabel)

            BIND("lu" AS ?targetType)
        BIND("resource_lu" AS ?type)
    }}
    """

     return GraphDB.query(sparql)
    @staticmethod
    def learningunit_resource_relations(course_uri):

      sparql = f"""
    PREFIX ade: <http://forjagraph.eus/onto#>

    SELECT ?source ?target ?sourceLabel ?targetLabel ?type ?sourceType ?targetType
    WHERE {{

        BIND(<{course_uri}> AS ?course)

        ?course ade:containsLearningUnit ?source .

        OPTIONAL {{
            ?source ade:name ?sourceLabel .
        }}

        OPTIONAL {{
            ?target ade:relatedToLearningUnit ?source .
            OPTIONAL {{ ?target ade:name ?targetLabel }}
            OPTIONAL {{ ?target ade:resourceType ?resourceTypeValue }}
            BIND(COALESCE(?resourceTypeValue, "resource") AS ?targetType)
        }}
            BIND("lu" AS ?sourceType)
        BIND("lu_resource" AS ?type)
    }}
    ORDER BY ?source ?target
    """

      return GraphDB.query(sparql)

    @staticmethod
    def assessmentitem_learningunit_relations(course_uri):

      sparql = f"""
    PREFIX ade: <http://forjagraph.eus/onto#>
    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

    SELECT ?source ?target ?sourceLabel ?targetLabel ?type ?sourceType ?targetType
    WHERE {{

        BIND(<{course_uri}> AS ?course)

        ?course ade:hasAssessmentItem ?source .

        OPTIONAL {{ ?source rdfs:label ?l1 }}
        OPTIONAL {{ ?source ade:name ?l2 }}
        BIND(COALESCE(?l1, ?l2) AS ?sourceLabel)

        OPTIONAL {{ ?source ade:resourceType ?resourceTypeValue }}
        BIND(COALESCE(?resourceTypeValue, "assessment") AS ?type)
        BIND(COALESCE(?resourceTypeValue, "assessment") AS ?sourceType)

        OPTIONAL {{
            ?source ade:relatedToLearningUnit ?target .
            
            OPTIONAL {{ ?target rdfs:label ?l3 }}
            OPTIONAL {{ ?target ade:name ?l4 }}
            BIND(COALESCE(?l3, ?l4) AS ?targetLabel)

            BIND("lu" AS ?targetType)
        }}
    }}
    ORDER BY ?source ?target
    """

      return GraphDB.query(sparql)
 
  

    @staticmethod
    def course_structure(course_uri):

     sparql = f"""
PREFIX ade: <http://forjagraph.eus/onto#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?source ?target ?sourceLabel ?targetLabel ?type
WHERE {{

    BIND(<{course_uri}> AS ?course)

    ?course ade:containsLearningUnit ?source .

    OPTIONAL {{
        ?source rdfs:label ?sourceLabel .
    }}

    OPTIONAL {{

        {{
            ?source ade:hasSubLearningUnit ?target .
            BIND("sub" AS ?type)
        }}
        UNION
        {{
            ?source ade:prerequisiteLearningUnit ?target .
            BIND("pre" AS ?type)
        }}

        OPTIONAL {{
            ?target rdfs:label ?targetLabel .
        }}
    }}
}}

ORDER BY ?source
    """
     return GraphDB.query(sparql)

 
    
    @staticmethod
    def get_courses():
       sparql = """
    PREFIX ade: <http://forjagraph.eus/onto#>

    SELECT ?course ?name
    WHERE {
        ?course a ade:Course .

        OPTIONAL {
            ?course ade:name ?name .
        }
    }
    ORDER BY ?name
    """

       return GraphDB.query(sparql)