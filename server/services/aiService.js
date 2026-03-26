require('dotenv').config({ path: '../.env' });
const OpenAI = require('openai');
const LearningPath = require('../models/LearningPath');

if (!process.env.OPENAI_API_KEY) {
  throw new Error("OPENAI_API_KEY is missing");
}

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

// Generate a personalized learning path using AI
const generateLearningPath = async (user, topic, preferences = {}) => {
  try {
    const { learningStyle, currentLevel } = user;
    const { timeCommitment, specificTopics, learningGoals } = preferences;

    // Construct the prompt for the AI
    const prompt = `Create a personalized learning path for a ${currentLevel} learner interested in ${topic}. 
    Learning style: ${learningStyle}.
    Time commitment: ${timeCommitment || 'flexible'}.
    Specific topics to cover: ${specificTopics?.join(', ') || 'none specified'}.
    Learning goals: ${learningGoals || 'general understanding'}.
    
    Please provide a structured learning path with modules, each containing resources and activities. 
    Format as a JSON object with this structure:
    {
      "title": "Learning Path Title",
      "description": "Brief description of the learning path",
      "estimatedHours": 10,
      "modules": [
        {
          "title": "Module Title",
          "description": "What will be learned in this module",
          "resources": [
            {
              "title": "Resource Title",
              "type": "video|article|interactive|quiz|project",
              "url": "(if applicable)",
              "description": "Brief description of the resource"
            }
          ]
        }
      ]
    }`;

    const completion = await openai.chat.completions.create({
      model: "gpt-4",
      messages: [
        {
          role: "system",
          content: "You are an expert learning experience designer. Create detailed, structured learning paths with appropriate resources based on the user's level and preferences."
        },
        {
          role: "user",
          content: prompt
        }
      ],
      temperature: 0.7,
      max_tokens: 1500
    });

    // Parse the AI response
    const content = completion.choices[0].message.content;
    let learningPathData;

    try {
      // Try to parse the JSON directly
      learningPathData = JSON.parse(content);
    } catch (e) {
      // If direct parsing fails, try to extract JSON from markdown code blocks
      const jsonMatch = content.match(/```json\n([\s\S]*?)\n```/);
      if (jsonMatch) {
        learningPathData = JSON.parse(jsonMatch[1]);
      } else {
        throw new Error('Could not parse AI response as JSON');
      }
    }

    // Create and save the learning path
    const learningPath = new LearningPath({
      ...learningPathData,
      createdBy: user._id,
      category: topic.toLowerCase(),
      difficulty: currentLevel,
      isPublic: false,
      enrolledUsers: [{
        user: user._id,
        progress: 0,
        completed: false,
        lastAccessed: new Date()
      }]
    });

    await learningPath.save();

    // Add the learning path to the user's profile
    user.completedPaths.push(learningPath._id);
    await user.save();

    return learningPath;
  } catch (error) {
    console.error('Error generating learning path:', error);
    throw new Error('Failed to generate learning path');
  }
};

// Get recommendations for additional resources
const getResourceRecommendations = async (topic, currentResources = []) => {
  try {
    const prompt = `I'm learning about ${topic} and have already used these resources: ${currentResources.join(', ') || 'none'}. 
    Suggest 3-5 additional high-quality resources (articles, videos, tutorials) that would help me learn more. 
    Format as a JSON array of objects with title, url, and type properties.`;

    const completion = await openai.chat.completions.create({
      model: "gpt-3.5-turbo",
      messages: [
        {
          role: "system",
          content: "You are a helpful learning assistant that recommends high-quality educational resources."
        },
        {
          role: "user",
          content: prompt
        }
      ],
      temperature: 0.7,
      max_tokens: 1000
    });

    // Parse and return the recommendations
    const content = completion.choices[0].message.content;
    let recommendations;

    try {
      recommendations = JSON.parse(content);
    } catch (e) {
      const jsonMatch = content.match(/```json\n([\s\S]*?)\n```/);
      if (jsonMatch) {
        recommendations = JSON.parse(jsonMatch[1]);
      } else {
        throw new Error('Could not parse recommendations as JSON');
      }
    }

    return Array.isArray(recommendations) ? recommendations : [];
  } catch (error) {
    console.error('Error getting resource recommendations:', error);
    return [];
  }
};

const improveLearningPath = async (learningPath, feedback) => {
  try {
    const prompt = `I have a learning path about ${learningPath.category} with these modules: 
    ${JSON.stringify(learningPath.modules.map(m => ({
      title: m.title,
      description: m.description,
      resourceCount: m.resources.length
    })), null, 2)}
    
    Here's my feedback: ${feedback}
    
    Please suggest specific improvements to make this learning path better. Focus on:
    1. Adding/modifying modules to cover gaps
    2. Suggesting better resource types or formats
    3. Improving the learning flow
    4. Adding practical exercises or projects
    
    Format your response as a JSON object with these fields: {
      "summary": "Brief overview of your suggestions",
      "moduleSuggestions": [{
        "action": "add|modify|remove",
        "moduleTitle": "Title of the module to add/modify",
        "suggestedChanges": "Detailed description of changes",
        "resources": [{
          "title": "Resource title",
          "type": "video|article|interactive|quiz|project",
          "url": "(if applicable)",
          "description": "Why this resource would be valuable"
        }]
      }]
    }`;

    const completion = await openai.chat.completions.create({
      model: "gpt-4",
      messages: [
        {
          role: "system",
          content: "You are an expert learning experience designer. Provide specific, actionable suggestions to improve learning paths."
        },
        {
          role: "user",
          content: prompt
        }
      ],
      temperature: 0.7,
      max_tokens: 1500
    });

    // Parse the AI response
    const content = completion.choices[0].message.content;
    let suggestions;

    try {
      suggestions = JSON.parse(content);
    } catch (e) {
      const jsonMatch = content.match(/```json\n([\s\S]*?)\n```/);
      if (jsonMatch) {
        suggestions = JSON.parse(jsonMatch[1]);
      } else {
        throw new Error('Could not parse AI suggestions as JSON');
      }
    }

    return suggestions;
  } catch (error) {
    console.error('Error improving learning path:', error);
    throw new Error('Failed to improve learning path');
  }
};

module.exports = {
  generateLearningPath,
  getResourceRecommendations,
  improveLearningPath
};
