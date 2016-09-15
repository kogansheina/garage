package garage;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.DocumentBuilder;
import java.io.File;
import org.w3c.dom.Document;
import org.w3c.dom.NodeList;
import org.w3c.dom.Node;
import org.w3c.dom.Element;
import java.util.ArrayList;

public class XMLFile 
{
    XMLFile(String filename, ArrayList<String>keywords, ArrayList<String>array)
    {
        try 
        {
            NodeList nList;
            Node nNode;
            
            File fXmlFile = new File(filename);
            DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
            DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
            Document doc = dBuilder.parse(fXmlFile);

            doc.getDocumentElement().normalize();
            for (int k = 0; k < keywords.size(); k++)
            {
                nList = doc.getElementsByTagName(keywords.get(k));
                for (int temp = 0; temp < nList.getLength(); temp++) 
                {
                    nNode = nList.item(temp);
                    if (nNode.getNodeType() == Node.ELEMENT_NODE) 
                    {
                        Element eElement = (Element) nNode;
                        String y = keywords.get(k).toLowerCase();
                        String s = eElement.getAttribute(y);
                        array.add(s);
                    }
                }
            }
        } 
        catch (Exception e) 
        {
            e.printStackTrace();
        }
    }
}


